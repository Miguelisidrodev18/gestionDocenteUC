<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\FinalResult;
use App\Models\ImprovementOpportunity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinalController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FinalResult::class);

        $periodo = $request->get('periodo', '2025-2');
        $sedeFilter = $request->get('sede');

        $resultsQuery = FinalResult::with('curso')
            ->where('periodo', $periodo);

        if ($sedeFilter) {
            $resultsQuery->where('sede', $sedeFilter);
        }

        $results = $resultsQuery->get();

        $summary = $results->groupBy('sede')->map(function ($group) {
            $totalAprob = (int) $group->sum('aprobados');
            $totalDesap = (int) $group->sum('desaprobados');
            $total = $totalAprob + $totalDesap;
            $avgProm = $group->avg('promedio') ?: 0;
            $avgAvance = $group->avg('avance_promedio') ?: 0;

            return [
                'aprobados' => $totalAprob,
                'desaprobados' => $totalDesap,
                'total' => $total,
                'porcentaje_aprobados' => $total > 0 ? round(($totalAprob / max(1, $total)) * 100, 2) : 0,
                'promedio' => round($avgProm, 2),
                'avance_promedio' => round($avgAvance, 2),
            ];
        })->toArray();

        $sedes = array_values(array_unique($results->pluck('sede')->all()));

        // Oportunidades de mejora asociadas a cursos del periodo
        $opportunitiesQuery = ImprovementOpportunity::with(['curso', 'owner'])
            ->whereHas('curso', function ($q) use ($periodo) {
                $q->where('periodo', $periodo);
            });

        if ($sedeFilter) {
            $opportunitiesQuery->where('sede', $sedeFilter);
        }

        $opportunities = $opportunitiesQuery
            ->orderBy('status')
            ->orderBy('due_date')
            ->get();

        // Cursos disponibles para crear oportunidades
        $coursesQuery = Curso::where('periodo', $periodo)->orderBy('nombre');
        $courses = $coursesQuery->get(['id', 'nombre', 'codigo']);

        return Inertia::render('Final/Index', [
            'periodo' => $periodo,
            'sede' => $sedeFilter,
            'sedes' => $sedes,
            'summary' => $summary,
            'results' => $results,
            'opportunities' => $opportunities,
            'courses' => $courses,
        ]);
    }

    public function storeOpportunity(Request $request)
    {
        $this->authorize('create', ImprovementOpportunity::class);

        $data = $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'sede' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'owner_user_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        $data['status'] = 'ABIERTA';
        $data['created_by'] = $request->user()->id;

        ImprovementOpportunity::create($data);

        return back()->with('success', 'Oportunidad de mejora creada.');
    }

    public function updateOpportunity(Request $request, ImprovementOpportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        $data = $request->validate([
            'descripcion' => 'sometimes|required|string',
            'owner_user_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'status' => 'required|string|in:ABIERTA,EN_PROGRESO,CERRADA',
        ]);

        $opportunity->update($data);

        return back()->with('success', 'Oportunidad actualizada.');
    }

    public function destroyOpportunity(Request $request, ImprovementOpportunity $opportunity)
    {
        $this->authorize('delete', $opportunity);
        $opportunity->delete();

        return back()->with('success', 'Oportunidad eliminada.');
    }
}

