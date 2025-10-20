<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Notification;
use App\Notifications\MeetingScheduled;

class MeetingController extends Controller
{
    /**
     * List meetings visible to the user and courses they can choose.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $accessibleCourseIds = $this->accessibleCourseIds($user);

        $meetings = Meeting::with(['curso', 'creator'])
            ->whereIn('curso_id', $accessibleCourseIds)
            ->orderBy('start_at')
            ->get();

        $cursos = Curso::whereIn('id', $accessibleCourseIds)
            ->with(['docente', 'responsable'])
            ->get();

        return Inertia::render('Horarios/Index', [
            'meetings' => $meetings,
            'cursos' => $cursos,
        ]);
    }

    /** Create a new meeting. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'location' => 'nullable|string|max:150',
        ]);

        $user = $request->user();
        $accessibleCourseIds = $this->accessibleCourseIds($user);
        if (! in_array((int) $data['curso_id'], $accessibleCourseIds, true)) {
            abort(403, 'No puedes crear reuniones para este curso.');
        }

        $data['created_by'] = $user->id;
        $meeting = Meeting::create($data);

        // Notificar a los involucrados del curso
        $curso = $meeting->curso()->with(['responsable', 'docente'])->first();
        $notifiables = collect();
        if ($curso?->responsable) { $notifiables->push($curso->responsable); }
        if ($curso?->docente && $curso->docente->user) { $notifiables->push($curso->docente->user); }
        $notifiables = $notifiables->unique('id');
        if ($notifiables->isNotEmpty()) {
            Notification::send($notifiables, new MeetingScheduled($meeting));
        }

        return back()->with('success', 'Reunión creada.');
    }

    /** Update an existing meeting. */
    public function update(Request $request, Meeting $meeting)
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'start_at' => 'sometimes|required|date',
            'end_at' => 'sometimes|required|date|after:start_at',
            'location' => 'nullable|string|max:150',
        ]);

        $this->authorizeAccess($request->user(), $meeting);

        $meeting->update($data);

        return back()->with('success', 'Reunión actualizada.');
    }

    /** Delete a meeting. */
    public function destroy(Request $request, Meeting $meeting)
    {
        $this->authorizeAccess($request->user(), $meeting);

        $meeting->delete();
        return back()->with('success', 'Reunión eliminada.');
    }

    private function accessibleCourseIds($user): array
    {
        if (! $user) return [];
        if ($user->isAdmin()) {
            return Curso::pluck('id')->all();
        }
        if ($user->isResponsable()) {
            return Curso::where('user_id', $user->id)->pluck('id')->all();
        }
        $docenteId = $user->docente?->id;
        if ($docenteId) {
            return Curso::where('docente_id', $docenteId)->pluck('id')->all();
        }
        return [];
    }

    private function authorizeAccess($user, Meeting $meeting): void
    {
        $allowed = false;
        if ($user->isAdmin()) {
            $allowed = true;
        } elseif ($user->isResponsable() && $meeting->curso && $meeting->curso->user_id === $user->id) {
            $allowed = true;
        } else {
            $docenteId = $user->docente?->id;
            if ($docenteId && $meeting->curso && $meeting->curso->docente_id === $docenteId) {
                $allowed = $meeting->created_by === $user->id; // Docente sólo modifica/elimina si es creador
            }
        }

        if (! $allowed) {
            abort(403, 'No tienes acceso a esta reunión.');
        }
    }
}
