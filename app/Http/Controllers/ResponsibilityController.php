<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentLog;
use App\Models\Curso;
use App\Models\User;
use App\Notifications\NewResponsibleAssigned;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class ResponsibilityController extends Controller
{
    public function index(Request $request): Response
    {
        $periodo = $request->get('periodo', '2025-2');
        $carreraId = $request->get('carrera'); // area_id
        $modalidadId = $request->get('modalidad');
        $responsableId = $request->get('responsable');

        $user = $request->user();

        $query = Curso::with([
            'docente',
            'responsable',
            'modalidadRel.area',
            'docentesParticipantes',
            'assignment.responsable',
        ])->where('periodo', $periodo);

        if ($carreraId) {
            $query->whereHas('modalidadRel.area', function ($q) use ($carreraId) {
                $q->where('id', $carreraId);
            });
        }

        if ($modalidadId) {
            $query->where('modalidad_id', $modalidadId);
        }

        if ($responsableId) {
            $query->whereHas('assignment', function ($q) use ($responsableId) {
                $q->where('responsable_user_id', $responsableId);
            });
        }

        if ($user && ! $user->isAdmin() && $user->isResponsable()) {
            $query->where('user_id', $user->id);
        }

        $cursos = $query->orderBy('nombre')->get();

        $docentesResponsables = User::whereIn('role', ['responsable', 'admin'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Responsabilidades/Index', [
            'cursos' => $cursos,
            'filters' => [
                'periodo' => $periodo,
                'carrera' => $carreraId,
                'modalidad' => $modalidadId,
                'responsable' => $responsableId,
            ],
            'responsables' => $docentesResponsables,
        ]);
    }

    public function update(Request $request, Curso $curso): RedirectResponse
    {
        $this->authorize('assignResponsible', $curso);

        $data = $request->validate([
            'responsable_user_id' => 'required|exists:users,id',
            'campus_id' => 'nullable|integer',
            'modalidad_docente' => 'nullable|string|max:100',
            'reason' => 'nullable|string',
        ]);

        /** @var Assignment $assignment */
        $assignment = Assignment::firstOrNew(['curso_id' => $curso->id]);
        $fromUserId = $assignment->responsable_user_id;
        $toUserId = (int) $data['responsable_user_id'];

        $assignment->fill([
            'responsable_user_id' => $toUserId,
            'campus_id' => $data['campus_id'] ?? null,
            'modalidad_docente' => $data['modalidad_docente'] ?? null,
            'assigned_at' => now(),
        ]);
        $assignment->save();

        AssignmentLog::create([
            'assignment_id' => $assignment->id,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'reason' => $data['reason'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        // Notificar al nuevo responsable
        $toUser = User::find($toUserId);
        if ($toUser) {
            Notification::send($toUser, new NewResponsibleAssigned($assignment));
        }

        return back()->with('success', 'Responsable actualizado correctamente.');
    }
}

