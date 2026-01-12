<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Curso;
use App\Models\RegistroNota;
use App\Models\Sede;
use App\Models\Area;
use App\Models\Modalidad;
use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = request()->user();

        // Vista docente: solo sus cursos y avances
        if ($user && method_exists($user, 'isDocente') && $user->isDocente()) {
            $docenteId = $user->docente?->id;
            $cursosMine = collect();
            if ($docenteId) {
                $cursos = Curso::with(['modalidadRel.area', 'evidencias'])
                    ->where('docente_id', $docenteId)
                    ->orWhereHas('docentesParticipantes', function ($q) use ($docenteId) {
                        $q->where('docente_id', $docenteId);
                    })
                    ->orderBy('nombre')
                    ->get();

                foreach ($cursos as $c) {
                    $req = $c->requerimientos();
                    $counts = $c->evidencias->groupBy('tipo')->map->count();
                    $faltantes = [];
                    foreach ($req as $tipo => $meta) {
                        $required = (int) ($meta['required'] ?? 0);
                        $have = (int) ($counts[$tipo] ?? 0);
                        $faltantes[$tipo] = max($required - $have, 0);
                    }
                    $cursosMine->push([
                        'id' => $c->id,
                        'codigo' => $c->codigo,
                        'nombre' => $c->nombre,
                        'periodo' => $c->periodo,
                        'modalidad' => $c->modalidadRel?->nombre ?? $c->modalidad,
                        'area' => $c->modalidadRel?->area?->nombre,
                        'avance' => $c->avance,
                        'faltantes' => $faltantes,
                    ]);
                }
            }

            return Inertia::render('Dashboard/Index', [
                'currentUserRole' => 'docente',
                'cursosDocente' => $cursosMine,
            ]);
        }

        // Vista administrativa: métricas generales
        $docentesCount = Docente::count();
        $cursosCount = Curso::count();
        $cursosPorPeriodo = Curso::select('periodo', DB::raw('count(*) as total'))
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $defaultPeriodo = $cursosPorPeriodo->last()->periodo ?? Curso::max('periodo') ?? null;
        $initialMetrics = $defaultPeriodo ? $this->computeMetrics($defaultPeriodo, null, null, null) : null;

        return Inertia::render('Dashboard/Index', [
            'docentesCount' => $docentesCount,
            'cursosCount' => $cursosCount,
            'cursosPorPeriodo' => $cursosPorPeriodo,
            'periodosCatalog' => PeriodoAcademico::orderByDesc('codigo')->get(['id', 'codigo', 'estado']),
            'sedesCatalog' => Sede::orderBy('nombre')->get(['id', 'nombre']),
            'areasCatalog' => Area::orderBy('nombre')->get(['id', 'nombre']),
            'modalidadesCatalog' => Modalidad::orderBy('nombre')->get(['id', 'nombre', 'area_id']),
            'currentUserRole' => $user?->role,
            'initialFilters' => [
                'periodo' => $defaultPeriodo,
                'sede' => null,
                'area_id' => null,
                'modalidad_id' => null,
            ],
            'initialMetrics' => $initialMetrics,
        ]);
    }

    public function metrics(Request $request)
    {
        $periodo = $request->get('periodo');
        $sede = $request->get('sede');
        $areaId = $request->get('area_id');
        $modalidadId = $request->get('modalidad_id');

        if (! $periodo) {
            $periodo = Curso::max('periodo');
        }

        $data = $this->computeMetrics($periodo, $sede, $areaId, $modalidadId);

        return response()->json($data);
    }

    protected function computeMetrics(?string $periodo, ?string $sede, ?string $areaId, ?string $modalidadId): array
    {
        if (! $periodo) {
            return [
                'periodo' => null,
                'sede' => $sede,
                'area_id' => $areaId,
                'modalidad_id' => $modalidadId,
                'resumen' => null,
                'top_riesgo' => [],
                'por_responsable' => [],
                'series_mensuales' => [],
            ];
        }

        $cacheKey = sprintf(
            'dashboard_metrics_%s_%s_%s_%s',
            $periodo,
            $sede ?: 'all',
            $areaId ?: 'all',
            $modalidadId ?: 'all',
        );

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($periodo, $sede, $areaId, $modalidadId) {
            $cursosQuery = Curso::with(['responsable', 'registroNotas'])
                ->where('periodo', $periodo);

            if ($sede) {
                $cursosQuery->whereHas('sede', function ($q) use ($sede) {
                    $q->where('nombre', $sede);
                });
            }

            if ($areaId) {
                $cursosQuery->where('area_id', $areaId);
            }

            if ($modalidadId) {
                $cursosQuery->where('modalidad_id', $modalidadId);
            }

            $cursos = $cursosQuery->get();
            $totalCursos = $cursos->count();

            $avances = $cursos->map(fn ($c) => (int) $c->avance);
            $avgAvance = $totalCursos ? round($avances->avg(), 1) : 0.0;
            $cursosRiesgo = $avances->filter(fn ($a) => $a < 60)->count();

            $topRiesgo = $cursos
                ->sortBy('avance')
                ->take(5)
                ->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'codigo' => $c->codigo,
                        'nombre' => $c->nombre,
                        'responsable' => $c->responsable?->name,
                        'avance' => (int) $c->avance,
                    ];
                })
                ->values();

            $porResponsable = $cursos
                ->groupBy('user_id')
                ->map(function ($group) {
                    $first = $group->first();
                    $avg = round($group->avg(fn ($c) => (int) $c->avance), 1);
                    return [
                        'responsable_id' => $first->user_id,
                        'responsable' => $first->responsable?->name ?? 'Sin responsable',
                        'cursos' => $group->count(),
                        'avance_promedio' => $avg,
                    ];
                })
                ->values()
                ->sortByDesc('cursos')
                ->values();

            // Series mensuales basadas en registros de notas (promedio EP por mes)
            $registrosQuery = RegistroNota::query()
                ->whereHas('curso', function ($q) use ($periodo) {
                    $q->where('periodo', $periodo);
                });

            if ($sede) {
                $registrosQuery->whereHas('curso.sede', function ($q) use ($sede) {
                    $q->where('nombre', $sede);
                });
            }

            if ($areaId) {
                $registrosQuery->whereHas('curso', function ($q) use ($areaId) {
                    $q->where('area_id', $areaId);
                });
            }

            if ($modalidadId) {
                $registrosQuery->whereHas('curso', function ($q) use ($modalidadId) {
                    $q->where('modalidad_id', $modalidadId);
                });
            }

            $seriesMensuales = $registrosQuery
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as mes, AVG(ep_promedio) as ep_promedio_prom')
                ->groupBy('mes')
                ->orderBy('mes')
                ->get()
                ->map(function ($row) {
                    return [
                        'mes' => $row->mes,
                        'ep_promedio' => round((float) $row->ep_promedio_prom, 2),
                    ];
                });

            return [
                'periodo' => $periodo,
                'sede' => $sede,
                'area_id' => $areaId,
                'modalidad_id' => $modalidadId,
                'resumen' => [
                    'total_cursos' => $totalCursos,
                    'avance_promedio' => $avgAvance,
                    'cursos_riesgo' => $cursosRiesgo,
                ],
                'top_riesgo' => $topRiesgo,
                'por_responsable' => $porResponsable,
                'series_mensuales' => $seriesMensuales,
            ];
        });
    }
}
