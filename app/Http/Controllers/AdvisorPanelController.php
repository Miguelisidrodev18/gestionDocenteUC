<?php

namespace App\Http\Controllers;

use App\Models\AdvisorAssignment;
use App\Models\AdvisorProfile;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Panel;
use App\Models\PanelLog;
use App\Notifications\PanelAssignmentStatusChanged;
use App\Notifications\PanelInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdvisorPanelController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $periodo = $request->get('periodo', '2025-2');
        $area = $request->get('area');
        $estado = $request->get('estado'); // invitado/aceptado/rechazado

        $docentesQuery = Docente::with(['user', 'cursos' => function ($q) use ($periodo) {
            $q->where('periodo', $periodo);
        }])
            ->with('cvDocuments')
            ->with(['cursosColabora' => function ($q) use ($periodo) {
                $q->where('periodo', $periodo);
            }])
            ->with('advisorProfile');

        if ($area) {
            $docentesQuery->where('especialidad', 'like', '%'.$area.'%');
        }

        $docentes = $docentesQuery->get();

        $panelsQuery = Panel::with(['curso.docente', 'assignments.docente'])
            ->whereHas('curso', function ($q) use ($periodo) {
                $q->where('periodo', $periodo);
            });

        if ($user->isResponsable() && ! $user->isAdmin()) {
            $panelsQuery->whereHas('curso', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($estado) {
            $panelsQuery->whereHas('assignments', function ($q) use ($estado) {
                $q->where('status', $estado);
            });
        }

        $panels = $panelsQuery->orderByDesc('scheduled_at')->get();

        $invitaciones = collect();
        if ($user->isDocente()) {
            $docenteId = $user->docente?->id;
            if ($docenteId) {
                $invitaciones = AdvisorAssignment::with('panel.curso')
                    ->where('docente_id', $docenteId)
                    ->orderByDesc('created_at')
                    ->get();
            }
        }

        return Inertia::render('Advisors/Index', [
            'periodo' => $periodo,
            'filters' => [
                'area' => $area,
                'estado' => $estado,
            ],
            'docentes' => $docentes,
            'panels' => $panels,
            'invitaciones' => $invitaciones,
            'canManage' => $user->isAdmin() || $user->isResponsable(),
        ]);
    }

    public function storePanel(Request $request)
    {
        $user = $request->user();
        $this->authorize('create', Panel::class);

        $data = $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'type' => 'required|string|max:50',
            'scheduled_at' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'max_members' => 'required|integer|min:1|max:10',
        ]);

        $curso = Curso::findOrFail($data['curso_id']);

        // No duplicar paneles confirmados para mismo curso/tipo
        $exists = Panel::where('curso_id', $curso->id)
            ->where('type', $data['type'])
            ->whereIn('status', ['pendiente', 'confirmado'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Ya existe un panel activo para este curso y tipo.');
        }

        $panel = Panel::create([
            'curso_id' => $curso->id,
            'type' => $data['type'],
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'location' => $data['location'] ?? null,
            'status' => 'borrador',
            'max_members' => $data['max_members'],
            'created_by' => $user->id,
        ]);

        PanelLog::create([
            'panel_id' => $panel->id,
            'user_id' => $user->id,
            'action' => 'created',
            'details' => 'Panel creado para curso '.$curso->codigo,
        ]);

        return back()->with('success', 'Panel creado correctamente.');
    }

    public function storeAssignment(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'panel_id' => 'required|exists:panels,id',
            'docente_id' => 'required|exists:docentes,id',
            'role' => 'required|string|max:50',
        ]);

        $panel = Panel::with('assignments', 'curso')->findOrFail($data['panel_id']);

        $this->authorize('update', $panel);

        if ($panel->assignments->count() >= $panel->max_members) {
            return back()->with('error', 'El panel ya alcanzó el máximo de miembros.');
        }

        $exists = $panel->assignments->firstWhere('docente_id', (int) $data['docente_id']);
        if ($exists) {
            return back()->with('error', 'El docente ya está invitado a este panel.');
        }

        $assignment = null;

        DB::transaction(function () use ($panel, $data, $user, &$assignment) {
            $assignment = AdvisorAssignment::create([
                'panel_id' => $panel->id,
                'docente_id' => $data['docente_id'],
                'role' => strtolower($data['role']),
                'status' => 'invitado',
                'invited_by' => $user->id,
            ]);

            PanelLog::create([
                'panel_id' => $panel->id,
                'user_id' => $user->id,
                'action' => 'invited',
                'details' => 'Invitado docente ID '.$data['docente_id'].' como '.$data['role'],
            ]);
        });

        $docente = $assignment->docente;
        $notifiableUser = $docente?->user;
        if ($notifiableUser) {
            $notifiableUser->notify(new PanelInvitationNotification($assignment));
        }

        return back()->with('success', 'Invitación enviada.');
    }

    public function acceptAssignment(Request $request, AdvisorAssignment $assignment)
    {
        $this->authorize('updateStatus', $assignment);

        if ($assignment->status !== 'invitado') {
            return back()->with('info', 'La invitación ya fue respondida.');
        }

        $assignment->update([
            'status' => 'aceptado',
            'responded_at' => now(),
        ]);

        PanelLog::create([
            'panel_id' => $assignment->panel_id,
            'user_id' => $request->user()->id,
            'action' => 'accepted',
            'details' => 'Invitación aceptada por docente ID '.$assignment->docente_id,
        ]);

        $creator = $assignment->panel->creator;
        if ($creator) {
            $creator->notify(new PanelAssignmentStatusChanged($assignment));
        }

        return back()->with('success', 'Has aceptado la invitación al panel.');
    }

    public function rejectAssignment(Request $request, AdvisorAssignment $assignment)
    {
        $this->authorize('updateStatus', $assignment);

        if ($assignment->status !== 'invitado') {
            return back()->with('info', 'La invitación ya fue respondida.');
        }

        $assignment->update([
            'status' => 'rechazado',
            'responded_at' => now(),
        ]);

        PanelLog::create([
            'panel_id' => $assignment->panel_id,
            'user_id' => $request->user()->id,
            'action' => 'rejected',
            'details' => 'Invitación rechazada por docente ID '.$assignment->docente_id,
        ]);

        $creator = $assignment->panel->creator;
        if ($creator) {
            $creator->notify(new PanelAssignmentStatusChanged($assignment));
        }

        return back()->with('success', 'Has rechazado la invitación al panel.');
    }
}

