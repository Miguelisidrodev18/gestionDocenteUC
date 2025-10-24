<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Curso;
use Illuminate\Http\Request;
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
        $cursosPorPeriodo = Curso::select('periodo', \DB::raw('count(*) as total'))
            ->groupBy('periodo')
            ->get();

        return Inertia::render('Dashboard/Index', [
            'docentesCount' => $docentesCount,
            'cursosCount' => $cursosCount,
            'cursosPorPeriodo' => $cursosPorPeriodo,
            'currentUserRole' => $user?->role,
        ]);
    }
}
