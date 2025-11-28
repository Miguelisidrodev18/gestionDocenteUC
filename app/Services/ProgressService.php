<?php

namespace App\Services;

use App\Models\Curso;
use App\Models\RequisitoModalidad;
use App\Models\TipoEvidencia;
use App\Models\FinalResult;

class ProgressService
{
    public function recomputeForCourse(int $courseId): int
    {
        /** @var Curso $curso */
        $curso = Curso::with(['evidencias', 'actas', 'registroNotas', 'informeFinal'])
            ->findOrFail($courseId);

        $config = config('requirements');
        $weights = $config['weights'] ?? [];

        $requisitosCatalogo = null;
        if ($curso->modalidad_id) {
            $requisitosCatalogo = RequisitoModalidad::with('tipo')
                ->where('modalidad_id', $curso->modalidad_id)
                ->get();
        }

        $requiredActas = 0;
        $requiredGuias = 0;
        $requiredPresent = 0;
        $requiredTrabajos = 0;
        $finalesCfg = [];

        if ($requisitosCatalogo && $requisitosCatalogo->isNotEmpty()) {
            foreach ($requisitosCatalogo as $req) {
                /** @var TipoEvidencia $tipo */
                $tipo = $req->tipo;
                if (! $tipo) {
                    continue;
                }
                $codigo = $tipo->codigo;
                if ($codigo === 'acta') {
                    $requiredActas += $req->minimo;
                } elseif ($codigo === 'guia') {
                    $requiredGuias += $req->minimo;
                } elseif ($codigo === 'presentacion') {
                    $requiredPresent += $req->minimo;
                } elseif ($codigo === 'trabajo') {
                    $requiredTrabajos += $req->minimo;
                } elseif (in_array($codigo, ['registro', 'informe_final', 'acta_final'], true)) {
                    $finalesCfg[$codigo] = 1;
                }
            }
        } else {
            $mod = strtolower((string) $curso->modalidad);
            $modeKey = str_contains($mod, 'semi') ? 'semipresencial' : 'presencial';
            $modeCfg = $config[$modeKey] ?? $config['presencial'];

            $requiredActas = (int) ($modeCfg['acta'] ?? 0);
            $requiredGuias = (int) ($modeCfg['guia'] ?? 0);
            $requiredPresent = (int) ($modeCfg['presentacion'] ?? 0);
            $requiredTrabajos = (int) ($modeCfg['trabajo'] ?? 0);

            if (isset($modeCfg['per_block'])) {
                foreach ($modeCfg['per_block'] as $blockCfg) {
                    $requiredGuias += (int) ($blockCfg['guia'] ?? 0);
                    $requiredPresent += (int) ($blockCfg['presentacion'] ?? 0);
                    $requiredTrabajos += (int) ($blockCfg['trabajo'] ?? 0);
                }
            }

            $finalesCfg = $modeCfg['finales'] ?? [];
        }

        // Hechos
        $actasDone = (int) $curso->actas->count();
        $evCounts = $curso->evidencias
            ->groupBy('tipo')
            ->map->count();

        $guiasDone = (int) ($evCounts['guia'] ?? 0);
        $presentDone = (int) ($evCounts['presentacion'] ?? 0);
        $trabajosDone = (int) ($evCounts['trabajo'] ?? 0);

        // Finales: acta_final, registro, informe_final
        $finalRequired = 0;
        $finalDone = 0;
        foreach ($finalesCfg as $key => $req) {
            $req = (int) $req;
            if ($req <= 0) {
                continue;
            }
            $finalRequired++;
            $has = false;
            switch ($key) {
                case 'acta_final':
                    // Simplificación: se considera acta final si hay al menos 1 acta
                    $has = $actasDone > 0;
                    break;
                case 'registro':
                    $has = $curso->registroNotas && $curso->registroNotas->count() > 0;
                    break;
                case 'informe_final':
                    $has = (bool) $curso->informeFinal;
                    break;
            }
            if ($has) {
                $finalDone++;
            }
        }

        // Ratios por grupo (0..1)
        $ratios = [
            'actas' => $requiredActas > 0 ? min($actasDone / $requiredActas, 1) : 0,
            'guias' => $requiredGuias > 0 ? min($guiasDone / $requiredGuias, 1) : 0,
            'presentaciones' => $requiredPresent > 0 ? min($presentDone / $requiredPresent, 1) : 0,
            'trabajos' => $requiredTrabajos > 0 ? min($trabajosDone / $requiredTrabajos, 1) : 0,
            'finales' => $finalRequired > 0 ? min($finalDone / $finalRequired, 1) : 0,
        ];

        $avance = 0.0;
        foreach ($ratios as $group => $ratio) {
            $weight = (float) ($weights[$group] ?? 0);
            $avance += $weight * $ratio;
        }

        $avancePercent = (int) round($avance * 100);

        $curso->avance_cache = $avancePercent;
        $curso->save();

        $this->updateFinalResultsForCourse($curso);

        return $avancePercent;
    }

    protected function updateFinalResultsForCourse(Curso $curso): void
    {
        $registros = $curso->registroNotas;
        if (! $registros || $registros->isEmpty()) {
            // Si no hay registros, limpiar final_results del curso
            FinalResult::where('curso_id', $curso->id)->delete();
            return;
        }

        $grouped = $registros->groupBy(function ($r) {
            return $r->campus ?: 'General';
        });

        $activeSedes = [];

        foreach ($grouped as $sede => $items) {
            $totalEstudiantes = (int) $items->sum('total_estudiantes');
            $aprobados = (int) $items->sum('ep_aprobados');
            $desaprobados = (int) $items->sum('ep_desaprobados');
            $avgPromedio = (float) ($items->avg('ep_promedio') ?? 0);
            $avancePromedio = $totalEstudiantes > 0
                ? round(($aprobados / max(1, $totalEstudiantes)) * 100, 2)
                : 0.0;

            FinalResult::updateOrCreate(
                [
                    'curso_id' => $curso->id,
                    'sede' => (string) $sede,
                ],
                [
                    'aprobados' => $aprobados,
                    'desaprobados' => $desaprobados,
                    'promedio' => $avgPromedio,
                    'avance_promedio' => $avancePromedio,
                    'periodo' => (string) $curso->periodo,
                ],
            );

            $activeSedes[] = (string) $sede;
        }

        // Eliminar sedes que ya no tienen registros de notas
        FinalResult::where('curso_id', $curso->id)
            ->whereNotIn('sede', $activeSedes)
            ->delete();
    }
}
