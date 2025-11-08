<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\User;
use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;
class CursoController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->get('periodo', '2025-2');
        $docenteFilter = $request->get('docente_id');
        $user = $request->user();

        $cursosQuery = Curso::with(['docente', 'responsable'])
            ->where('periodo', $periodo);

        if ($user) {
            if ($user->isAdmin()) {
                // Admin ve todos; si hay filtro por docente, aplicarlo
                if ($docenteFilter) {
                    $cursosQuery->where('docente_id', $docenteFilter);
                }
            } elseif ($user->isResponsable()) {
                $cursosQuery->where('user_id', $user->id);
            } else {
                $docenteId = $user->docente?->id;
                $cursosQuery->where(function ($query) use ($user, $docenteId) {
                    $query->where('user_id', $user->id);

                    if ($docenteId) {
                        $query->orWhere('docente_id', $docenteId);
                    }
                });
            }
        }

        $cursos = $cursosQuery->with(['evidencias','modalidadRel.area'])->get();
        $docentes = Docente::all();
        $responsables = User::whereIn('role', ['responsable', 'admin'])->get();

        // Organizar por área -> modalidad -> cursos
        $areas = Area::with(['modalidades' => function($q) use ($user, $docenteFilter, $periodo) {
            $q->with(['cursos' => function($qc) use ($user, $docenteFilter, $periodo) {
                $qc->with(['docente','responsable'])
                   ->where('periodo', $periodo);
                if ($user) {
                    if ($user->isResponsable()) {
                        $qc->where('user_id', $user->id);
                    } elseif (! $user->isAdmin()) {
                        $docenteId = $user->docente?->id;
                        if ($docenteId) { $qc->where('docente_id', $docenteId); }
                    }
                }
                if ($docenteFilter) { $qc->where('docente_id', $docenteFilter); }
            }]);
        }])->get();

        return Inertia::render('Cursos/List', [
            'areas' => $areas,
            'cursos' => $cursos,
            'docentes' => $docentes,
            'responsables' => $responsables,
            'periodo' => $periodo,
            'filters' => [
                'docente_id' => $docenteFilter,
            ],
            'currentDocenteId' => $user?->docente?->id,
            'currentUserRole' => $user?->role,
        ]);
    }
    public function store(Request $request)
{
    $request->validate([
        'nombre' => 'required|string|max:150',
        'codigo' => 'required|string|unique:cursos,codigo',
        'descripcion' => 'nullable|string',
        'creditos' => 'required|integer',
        'nivel' => 'required|in:pregrado,postgrado',
        'modalidad' => 'required|string',
        'modalidad_id' => 'nullable|exists:modalidades,id',
        'docente_id' => 'required|exists:docentes,id',
        'drive_url' => 'nullable|url',
        'periodo' => 'required|string',
        'periodo_academico' => 'nullable|string',
        'responsable_id' => 'nullable|exists:users,id',
        'docentes_ids' => 'array',
        'docentes_ids.*' => 'exists:docentes,id',
    ]);

    $curso = Curso::create([
        'nombre' => $request->nombre,
        'codigo' => $request->codigo,
        'descripcion' => $request->descripcion,
        'creditos' => $request->creditos,
        'nivel' => $request->nivel,
        'modalidad' => $request->modalidad,
        'modalidad_id' => $request->modalidad_id,
        'docente_id' => $request->docente_id,
        'drive_url' => $request->drive_url,
        'user_id' => $request->responsable_id ?? $request->user()->id,
        'periodo' => $request->periodo,
        'periodo_academico' => $request->periodo_academico,
    ]);

    if ($request->filled('docentes_ids')) {
        $curso->docentesParticipantes()->sync($request->input('docentes_ids'));
    }

    return redirect()->route('cursos.index')->with('success', 'Curso creado correctamente');
}
public function edit($id)
{
    $curso = Curso::with(['docente', 'responsable'])->findOrFail($id);
    $docentes = Docente::all();
    $responsables = User::where('role', 'responsable')->get();

    return Inertia::render('Cursos/Edit', [
        'curso' => $curso,
        'docentes' => $docentes,
        'responsables' => $responsables,
    ]);
}

public function show($id)
{
    $baseWith = [
        'docente','responsable','modalidadRel.area',
        'evidencias' => function($q){ $q->latest(); },
        'actas' => function($q){ $q->latest(); },
    ];
    $curso = Curso::with($baseWith)->findOrFail($id);
    // Carga condicional de relaciones nuevas (evita error si falta migración)
    try { if (\Illuminate\Support\Facades\Schema::hasTable('registro_notas')) { $curso->load('registroNotas'); } } catch (\Throwable $e) {}
    try { if (\Illuminate\Support\Facades\Schema::hasTable('informe_finals')) { $curso->load('informeFinal'); } } catch (\Throwable $e) {}
    // Autorización: solo admin, responsable del curso o docente asignado/co-docente
    $user = request()->user();
    $allowed = false;
    if ($user) {
        if ($user->isAdmin()) {
            $allowed = true;
        } elseif ($user->isResponsable() && $curso->user_id === $user->id) {
            $allowed = true;
        } else {
            $docenteId = $user->docente?->id;
            if ($docenteId) {
                $allowed = $curso->docente_id === $docenteId
                    || $curso->docentesParticipantes()->where('docente_id', $docenteId)->exists();
            }
        }
    }
    if (! $allowed) {
        abort(403, 'No autorizado para ver este curso');
    }
    // Aggregation for registro de notas (totals by campus + averages)
    $aggregate = [];
    $registros = method_exists($curso, 'registroNotas') && $curso->relationLoaded('registroNotas') ? $curso->getRelation('registroNotas') : collect();
    foreach ($registros as $r) {
        $campus = $r->campus ?: 'General';
        if (!isset($aggregate[$campus])) {
            $aggregate[$campus] = [
                'total_estudiantes' => 0,
                'c1_aprobados' => 0,
                'c1_desaprobados' => 0,
                'c1_promedio_sum' => 0,
                'c1_promedio_count' => 0,
                'ep_aprobados' => 0,
                'ep_desaprobados' => 0,
                'ep_promedio_sum' => 0,
                'ep_promedio_count' => 0,
            ];
        }
        $aggregate[$campus]['total_estudiantes'] += (int) $r->total_estudiantes;
        $aggregate[$campus]['c1_aprobados'] += (int) $r->c1_aprobados;
        $aggregate[$campus]['c1_desaprobados'] += (int) $r->c1_desaprobados;
        $aggregate[$campus]['c1_promedio_sum'] += (float) $r->c1_promedio;
        $aggregate[$campus]['c1_promedio_count'] += 1;
        $aggregate[$campus]['ep_aprobados'] += (int) $r->ep_aprobados;
        $aggregate[$campus]['ep_desaprobados'] += (int) $r->ep_desaprobados;
        $aggregate[$campus]['ep_promedio_sum'] += (float) $r->ep_promedio;
        $aggregate[$campus]['ep_promedio_count'] += 1;
    }
    $aggregateFormatted = [];
    foreach ($aggregate as $campus => $a) {
        $aggregateFormatted[$campus] = [
            'total_estudiantes' => $a['total_estudiantes'],
            'c1_aprobados' => $a['c1_aprobados'],
            'c1_porcentaje' => $a['total_estudiantes'] > 0 ? round(($a['c1_aprobados'] / max(1,$a['total_estudiantes']))*100,2) : 0,
            'ep_aprobados' => $a['ep_aprobados'],
            'ep_porcentaje' => $a['total_estudiantes'] > 0 ? round(($a['ep_aprobados'] / max(1,$a['total_estudiantes']))*100,2) : 0,
            'c1_promedio' => $a['c1_promedio_count'] ? round($a['c1_promedio_sum']/$a['c1_promedio_count'],2) : 0,
            'ep_promedio' => $a['ep_promedio_count'] ? round($a['ep_promedio_sum']/$a['ep_promedio_count'],2) : 0,
        ];
    }

    return Inertia::render('Cursos/Show', [
        'curso' => $curso,
        'requerimientos' => $curso->requerimientos(),
        'avance' => $curso->avance,
        'registroAggregate' => $aggregateFormatted,
    ]);
}
public function destroy($id)
{
    $curso = Curso::findOrFail($id);
    $curso->delete();

    return redirect()->route('cursos.index')->with('success', 'Curso eliminado correctamente');
}
public function update(Request $request, $id)
{
    $request->validate([
        'nombre' => 'required|string|max:150',
        'codigo' => 'required|string|unique:cursos,codigo,' . $id,
        'descripcion' => 'nullable|string',
        'creditos' => 'required|integer',
        'nivel' => 'required|in:pregrado,postgrado',
        'modalidad' => 'required|string',
        'modalidad_id' => 'nullable|exists:modalidades,id',
        'docente_id' => 'required|exists:docentes,id',
        'drive_url' => 'nullable|url',
        'responsable_id' => 'nullable|exists:users,id',
        'periodo_academico' => 'nullable|string',
        'docentes_ids' => 'array',
        'docentes_ids.*' => 'exists:docentes,id',
    ]);

    $curso = Curso::findOrFail($id);
    $curso->update([
        'nombre' => $request->nombre,
        'codigo' => $request->codigo,
        'descripcion' => $request->descripcion,
        'creditos' => $request->creditos,
        'nivel' => $request->nivel,
        'modalidad' => $request->modalidad,
        'modalidad_id' => $request->modalidad_id,
        'docente_id' => $request->docente_id,
        'drive_url' => $request->drive_url,
        'user_id' => $request->responsable_id ?? $curso->user_id,
        'periodo_academico' => $request->periodo_academico ?? $curso->periodo_academico,
    ]);

    if ($request->filled('docentes_ids')) {
        $curso->docentesParticipantes()->sync($request->input('docentes_ids'));
    }

    return redirect()->route('cursos.index')->with('success', 'Curso actualizado correctamente');
}

    /**
     * JSON: cursos del docente (principal o co-docente) por periodo.
     */
    public function forDocente(Request $request, Docente $docente)
    {
        $periodo = $request->get('periodo');

        $cursosQuery = Curso::with(['modalidadRel.area'])
            ->where(function ($q) use ($docente) {
                $q->where('docente_id', $docente->id)
                  ->orWhereHas('docentesParticipantes', function ($p) use ($docente) {
                      $p->where('docente_id', $docente->id);
                  });
            });

        if ($periodo) {
            $cursosQuery->where('periodo', $periodo);
        }

        $cursos = $cursosQuery->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'periodo' => $c->periodo,
                'modalidad' => $c->modalidad,
                'modalidad_id' => $c->modalidad_id,
                'duracion_semanas' => $c->modalidadRel?->duracion_semanas,
                'area' => $c->modalidadRel?->area?->nombre,
                'avance' => $c->avance,
                'requerimientos' => $c->requerimientos(),
            ];
        });

        return response()->json($cursos);
    }
}



