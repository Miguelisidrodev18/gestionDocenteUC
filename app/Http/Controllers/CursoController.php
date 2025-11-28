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
    public function assignments(Request $request)
    {
        $periodo = $request->get('periodo', '2025-2');
        $user = $request->user();

        $cursosQuery = Curso::with(['docente', 'docentesParticipantes', 'modalidadRel.area'])
            ->where('periodo', $periodo);

        if ($user && $user->isResponsable() && ! $user->isAdmin()) {
            $cursosQuery->where('user_id', $user->id);
        }

        $cursos = $cursosQuery
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Cursos/AsignacionResponsables', [
            'cursos' => $cursos,
            'periodo' => $periodo,
            'currentUserRole' => $user?->role,
        ]);
    }

    public function index(Request $request)
    {
        $periodo = $request->get('periodo', '2025-2');
        $docenteFilter = $request->get('docente_id');
        $modalidadFilter = $request->get('modalidad'); // texto: nombre/modalidad
        $campusFilter = $request->get('campus');
        $search = $request->get('q');
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

        if ($modalidadFilter) {
            $cursosQuery->where(function ($q) use ($modalidadFilter) {
                $term = '%'.$modalidadFilter.'%';
                $q->where('modalidad', 'like', $term)
                    ->orWhereHas('modalidadRel', function ($qm) use ($term) {
                        $qm->where('nombre', 'like', $term);
                    });
            });
        }

        if ($campusFilter) {
            $cursosQuery->whereHas('registroNotas', function ($q) use ($campusFilter) {
                $q->where('campus', 'like', '%'.$campusFilter.'%');
            });
        }

        if ($search) {
            $cursosQuery->where(function ($q) use ($search) {
                $term = '%'.$search.'%';
                $q->where('codigo', 'like', $term)
                    ->orWhere('nombre', 'like', $term)
                    ->orWhereHas('docente', function ($qd) use ($term) {
                        $qd->where('dni', 'like', $term)
                            ->orWhere('nombre', 'like', $term)
                            ->orWhere('apellido', 'like', $term);
                    })
                    ->orWhereHas('responsable', function ($qr) use ($term) {
                        $qr->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
            });
        }

        $cursos = $cursosQuery->with(['evidencias','modalidadRel.area','registroNotas'])->get();
        $docentes = Docente::all();
        $responsables = User::whereIn('role', ['responsable', 'admin'])->get();

        // Organizar por área -> modalidad -> cursos
        $areas = Area::with(['modalidades' => function($q) use ($user, $docenteFilter, $periodo, $modalidadFilter, $campusFilter, $search) {
            $q->with(['cursos' => function($qc) use ($user, $docenteFilter, $periodo, $modalidadFilter, $campusFilter, $search) {
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
                if ($modalidadFilter) {
                    $qc->where(function ($qmod) use ($modalidadFilter) {
                        $term = '%'.$modalidadFilter.'%';
                        $qmod->where('modalidad', 'like', $term)
                             ->orWhereHas('modalidadRel', function ($qm) use ($term) {
                                 $qm->where('nombre', 'like', $term);
                             });
                    });
                }
                if ($campusFilter) {
                    $qc->whereHas('registroNotas', function ($qcamp) use ($campusFilter) {
                        $qcamp->where('campus', 'like', '%'.$campusFilter.'%');
                    });
                }
                if ($search) {
                    $qc->where(function ($qq) use ($search) {
                        $term = '%'.$search.'%';
                        $qq->where('codigo', 'like', $term)
                            ->orWhere('nombre', 'like', $term)
                            ->orWhereHas('docente', function ($qd) use ($term) {
                                $qd->where('dni', 'like', $term)
                                    ->orWhere('nombre', 'like', $term)
                                    ->orWhere('apellido', 'like', $term);
                            })
                            ->orWhereHas('responsable', function ($qr) use ($term) {
                                $qr->where('name', 'like', $term)
                                    ->orWhere('email', 'like', $term);
                            });
                    });
                }
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
                'modalidad' => $modalidadFilter,
                'campus' => $campusFilter,
                'q' => $search,
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
    $this->authorize('update', $curso);
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
    $this->authorize('view', $curso);
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
    $this->authorize('update', $curso);
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

    public function updateResponsableDocente(Request $request, Curso $curso)
    {
        $request->validate([
            'docente_id' => 'required|exists:docentes,id',
        ]);

        $this->authorize('assignResponsible', $curso);

        $curso->docente_id = (int) $request->input('docente_id');
        $curso->save();

        $curso->docentesParticipantes()->syncWithoutDetaching([$curso->docente_id]);

        return back()->with('success', 'Docente responsable actualizado correctamente');
    }

    /**
     * Copia cursos desde el último periodo disponible al periodo indicado.
     * Solo se copian los cursos (sin documentos). Por compatibilidad con el
     * esquema actual se conserva docente y responsable; si se requiere
     * dejarlos nulos habría que volver las columnas nullable a nivel de BD.
     */
    public function importFromPrevious(Request $request)
    {
        $targetPeriodo = $request->input('periodo');
        if (! $targetPeriodo) {
            return back()->with('error', 'Debe seleccionar un periodo destino');
        }

        $user = $request->user();
        if (! $user || (! $user->isAdmin() && ! $user->isResponsable())) {
            abort(403);
        }

         // No permitir traer cursos si el periodo ya tiene al menos uno
         $existingInTarget = Curso::where('periodo', $targetPeriodo)->exists();
         if ($existingInTarget) {
             return back()->with('info', 'El periodo seleccionado ya tiene cursos, no es posible traer más.');
         }

        // Determinar periodo origen: el inmediatamente anterior entre los existentes
        $periodos = Curso::select('periodo')->distinct()->orderBy('periodo')->pluck('periodo')->values();
        $sourcePeriodo = null;
        $idx = $periodos->search($targetPeriodo);
        if ($idx !== false && $idx > 0) {
            $sourcePeriodo = $periodos[$idx - 1];
        } elseif ($periodos->count() > 0) {
            // Si el periodo destino aún no existe en la tabla,
            // tomar el último periodo registrado como origen.
            $sourcePeriodo = $periodos->last();
        }

        if (! $sourcePeriodo) {
            return back()->with('error', 'No se encontró un periodo anterior para copiar cursos.');
        }

        // Cursos origen según permisos
        $sourceQuery = Curso::where('periodo', $sourcePeriodo);
        if ($user->isResponsable() && ! $user->isAdmin()) {
            $sourceQuery->where('user_id', $user->id);
        }

        $sourceCursos = $sourceQuery->get();
        if ($sourceCursos->isEmpty()) {
            return back()->with('error', 'No hay cursos en el periodo anterior para copiar.');
        }

        $creados = 0;
        foreach ($sourceCursos as $curso) {
            // Evitar duplicar por mismo código y periodo destino
            $already = Curso::where('codigo', $curso->codigo)
                ->where('periodo', $targetPeriodo)
                ->exists();
            if ($already) {
                continue;
            }

            Curso::create([
                'nombre' => $curso->nombre,
                'codigo' => $curso->codigo,
                'descripcion' => $curso->descripcion,
                'creditos' => $curso->creditos,
                'nivel' => $curso->nivel,
                'modalidad' => $curso->modalidad,
                'modalidad_id' => $curso->modalidad_id,
                // Por restricciones de esquema mantenemos docente y responsable;
                // si más adelante se vuelven nullable se pueden limpiar aquí.
                'docente_id' => $curso->docente_id,
                'drive_url' => null,
                'user_id' => $curso->user_id,
                'periodo' => $targetPeriodo,
                'periodo_academico' => $curso->periodo_academico,
            ]);
            $creados++;
        }

        if (! $creados) {
            return back()->with('info', 'Ya existen cursos para este periodo con los mismos códigos.');
        }

        return back()->with('success', "Se copiaron {$creados} curso(s) desde el periodo {$sourcePeriodo}.");
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



