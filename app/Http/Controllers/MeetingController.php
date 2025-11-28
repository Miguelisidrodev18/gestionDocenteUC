<?php

namespace App\Http\Controllers;

use App\Models\Curso;
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
        $curso = Curso::with(['docentesParticipantes'])->findOrFail((int) $data['curso_id']);
        $this->authorize('create', [Meeting::class, $curso]);

        $data['created_by'] = $user->id;

        $startAt = $data['start_at'];
        $endAt = $data['end_at'];

        // Advertencia de choque de horario (no bloqueante)
        $docenteIds = collect();
        if ($curso->docente_id) {
            $docenteIds->push($curso->docente_id);
        }
        $docenteIds = $docenteIds
            ->merge($curso->docentesParticipantes->pluck('id'))
            ->unique()
            ->filter()
            ->values();

        $warning = null;
        if ($docenteIds->isNotEmpty()) {
            $overlapExists = Meeting::whereHas('curso', function ($qc) use ($docenteIds) {
                $qc->whereIn('docente_id', $docenteIds)
                    ->orWhereHas('docentesParticipantes', function ($qd) use ($docenteIds) {
                        $qd->whereIn('docente_id', $docenteIds);
                    });
            })
                ->where('start_at', '<', $endAt)
                ->where('end_at', '>', $startAt)
                ->exists();

            if ($overlapExists) {
                $warning = 'Advertencia: hay otra reunión para alguno de los docentes en este horario.';
            }
        }

        $meeting = Meeting::create($data);

        // Notificar a los involucrados del curso
        $curso = $meeting->curso()->with(['responsable', 'docente', 'docentesParticipantes.user'])->first();
        $notifiables = collect();
        if ($curso?->responsable) {
            $notifiables->push($curso->responsable);
        }
        if ($curso?->docente && $curso->docente->user) {
            $notifiables->push($curso->docente->user);
        }
        foreach ($curso->docentesParticipantes as $doc) {
            if ($doc->user) {
                $notifiables->push($doc->user);
            }
        }
        $notifiables = $notifiables->unique('id');
        if ($notifiables->isNotEmpty()) {
            Notification::send($notifiables, new MeetingScheduled($meeting));
        }

        return back()->with([
            'success' => 'Reunión creada.',
            'warning' => $warning,
        ]);
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

        $this->authorize('update', $meeting);

        $meeting->update($data);

        return back()->with('success', 'Reunión actualizada.');
    }

    /** Delete a meeting. */
    public function destroy(Request $request, Meeting $meeting)
    {
        $this->authorize('delete', $meeting);

        $meeting->delete();
        return back()->with('success', 'Reunión eliminada.');
    }

    private function accessibleCourseIds($user): array
    {
        if (! $user) {
            return [];
        }
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
}

