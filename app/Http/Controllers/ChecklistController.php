<?php

namespace App\Http\Controllers;

use App\Models\CourseDocument;
use App\Models\Curso;
use App\Services\ChecklistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChecklistController extends Controller
{
    public function __construct(
        protected ChecklistService $checklistService
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $sedeFilter = $request->get('sede');

        $cursos = Curso::with([
            'docente',
            'responsable',
            'documents' => fn ($query) => $query->latest(),
            'documents.docente',
            'registroNotas',
            'informeFinal',
        ])->when($user && ! $user->isAdmin(), function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        // Adjuntar estado de checklist calculado y sede
        $cursos->each(function (Curso $curso) {
            $curso->checklist_status = $this->checklistService->statusForCourse($curso->id);
            $curso->sede_label = $this->deriveSedeLabel($curso);
        });

        if ($sedeFilter) {
            $cursos = $cursos->filter(function (Curso $curso) use ($sedeFilter) {
                $label = (string) ($curso->sede_label ?? '');
                return stripos($label, $sedeFilter) !== false;
            })->values();
        }

        $sedes = $cursos->pluck('sede_label')->filter()->unique()->values();

        return Inertia::render('Cursos/Checklist', [
            'cursos' => $cursos,
            'sedes' => $sedes,
            'filters' => [
                'sede' => $sedeFilter,
            ],
        ]);
    }

    /**
     * Mantenido por compatibilidad: el checklist ya no se actualiza manualmente
     * a nivel de documentos. Los estados se calculan automáticamente.
     */
    public function update(Request $request, CourseDocument $document): RedirectResponse
    {
        // Este método ya no acepta toggles manuales sobre documentos.
        // Se mantiene para compatibilidad pero responde 405.
        abort(405, 'Actualización directa de checklist deshabilitada. El estado se calcula automáticamente.');
    }

    /**
     * Cambia el estado de revisión del curso (pendiente/observado/validado).
     */
    public function changeCourseState(Request $request, Curso $curso): RedirectResponse
    {
        $this->authorize('changeState', $curso);

        $data = $request->validate([
            'action' => 'required|string|in:pendiente,observado,validado',
        ]);

        $action = $data['action'];

        if ($action === 'validado') {
            // Solo permitir validar si todos los ítems están cumplidos
            $status = $this->checklistService->statusForCourse($curso->id);
            $allOk = collect($status)->every(fn ($s) => $s === 'cumplido');

            if (! $allOk) {
                return back()->with('error', 'No se puede validar: hay elementos del checklist pendientes.');
            }
        }

        $curso->review_state = $action;
        $curso->save();

        return back()->with('success', 'Estado de revisión actualizado.');
    }

    private function deriveSedeLabel(Curso $curso): string
    {
        // 1) Intentar a partir de resultados del informe final (keys = sedes)
        $label = '';
        if ($curso->relationLoaded('informeFinal') && $curso->informeFinal) {
            $resultados = $curso->informeFinal->resultados ?? [];
            if (is_array($resultados) && ! empty($resultados)) {
                $keys = array_keys($resultados);
                $label = implode(' / ', array_filter(array_map('strval', $keys)));
            }
        }

        // 2) Si no hay, derivar de los campus de registro de notas
        if ($label === '' && $curso->relationLoaded('registroNotas')) {
            $campus = $curso->registroNotas
                ->pluck('campus')
                ->filter()
                ->unique()
                ->values()
                ->all();
            if (! empty($campus)) {
                $label = implode(' / ', array_map('strval', $campus));
            }
        }

        if ($label === '') {
            $label = 'General';
        }

        return $label;
    }
}
