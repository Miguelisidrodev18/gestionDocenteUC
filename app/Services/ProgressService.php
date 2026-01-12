<?php

namespace App\Services;

use App\Models\Curso;
use App\Models\RequisitoModalidad;
use App\Models\TipoEvidencia;
use App\Models\FinalResult;
use App\Services\ChecklistService;

class ProgressService
{
    public function recomputeForCourse(int $courseId): int
    {
        /** @var Curso $curso */
        $curso = Curso::with(['evidencias', 'actas', 'registroNotas', 'informeFinal', 'modalidadRel'])
            ->findOrFail($courseId);

        $avanceChecklist = $this->computeFromChecklist($courseId);
        $curso->avance_cache = $avanceChecklist;
        $curso->save();
        $this->updateFinalResultsForCourse($curso);
        return $avanceChecklist;

        $requisitosCatalogo = collect();
        if ($curso->modalidad_id) {
            $requisitosCatalogo = RequisitoModalidad::with('tipo')
                ->where('modalidad_id', $curso->modalidad_id)
                ->get();
        }

        if ($requisitosCatalogo->isEmpty()) {
            $avanceFallback = $this->computeFromChecklist($courseId);
            $curso->avance_cache = $avanceFallback;
            $curso->save();
            $this->updateFinalResultsForCourse($curso);
            return $avanceFallback;
        }

        $manual = $curso->checklist_manual ?? [];
        if (is_array($manual) && ! empty($manual)) {
            $avanceManual = $this->computeFromChecklist($courseId);
            $curso->avance_cache = $avanceManual;
            $curso->save();
            $this->updateFinalResultsForCourse($curso);
            return $avanceManual;
        }

        $actasDone = (int) $curso->actas->count();
        $evByType = $curso->evidencias->groupBy('tipo');

        $numBloques = (int) ($curso->modalidadRel?->num_bloques ?? 0);
        $weeksPerBlock = (int) ($curso->modalidadRel?->semanas_por_bloque ?? 0);
        $useBlocks = ($curso->modalidadRel?->estructura_duracion === 'BLOQUES' && $numBloques > 0 && $weeksPerBlock > 0);

        $avance = 0.0;

        foreach ($requisitosCatalogo as $req) {
            /** @var TipoEvidencia $tipo */
            $tipo = $req->tipo;
            if (! $tipo) {
                continue;
            }

            $codigo = (string) $tipo->codigo;
            $minimo = max(0, (int) $req->minimo);
            $peso = max(0, (int) $req->peso);
            $aplica = (string) ($req->aplica_a ?? 'CICLO');

            $cumplimiento = 0.0;
            if ($minimo == 0) {
                $cumplimiento = 1.0;
            } elseif ($aplica === 'POR_BLOQUE' && $useBlocks) {
                $sum = 0.0;
                for ($i = 1; $i <= $numBloques; $i++) {
                    $entregados = $this->countForRequirement($codigo, $curso, $evByType, $actasDone, $i, $weeksPerBlock);
                    $sum += min($entregados / $minimo, 1);
                }
                $cumplimiento = $sum / $numBloques;
            } else {
                $entregados = $this->countForRequirement($codigo, $curso, $evByType, $actasDone, null, null);
                $cumplimiento = min($entregados / $minimo, 1);
            }

            $avance += $cumplimiento * $peso;
        }

        $avancePercent = (int) round(min($avance, 100));

        $curso->avance_cache = $avancePercent;
        $curso->save();

        $this->updateFinalResultsForCourse($curso);

        return $avancePercent;
    }

    private function countForRequirement(
        string $codigo,
        Curso $curso,
        $evByType,
        int $actasDone,
        ?int $blockIndex,
        ?int $weeksPerBlock,
    ): int {
        if ($codigo === 'acta') {
            return $actasDone;
        }
        if ($codigo === 'registro') {
            return $curso->registroNotas ? (int) $curso->registroNotas->count() : 0;
        }
        if ($codigo === 'informe_final') {
            return $curso->informeFinal ? 1 : 0;
        }

        $items = $evByType[$codigo] ?? collect();
        if ($blockIndex && $weeksPerBlock) {
            $start = ($blockIndex - 1) * $weeksPerBlock + 1;
            $end = $blockIndex * $weeksPerBlock;
            return (int) $items->filter(function ($ev) use ($start, $end) {
                $week = (int) ($ev->semana ?? 0);
                return $week >= $start && $week <= $end;
            })->count();
        }

        return (int) $items->count();
    }

    private function computeFromChecklist(int $courseId): int
    {
        $status = app(ChecklistService::class)->statusForCourse($courseId);
        if (! $status) {
            return 0;
        }

        $tipos = TipoEvidencia::orderBy('codigo')->get(['codigo', 'cuenta_en_avance']);
        $codes = $tipos->filter(fn ($t) => (bool) $t->cuenta_en_avance)->pluck('codigo')->all();
        if (! count($codes)) {
            $codes = $tipos->pluck('codigo')->all();
        }

        if (! count($codes)) {
            return 0;
        }

        $cumplidos = 0;
        foreach ($codes as $code) {
            if (($status[$code] ?? 'pendiente') === 'cumplido') {
                $cumplidos++;
            }
        }

        return (int) round(($cumplidos / count($codes)) * 100);
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
