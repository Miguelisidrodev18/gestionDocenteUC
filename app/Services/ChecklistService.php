<?php

namespace App\Services;

use App\Models\Curso;

class ChecklistService
{
    /**
     * Devuelve el estado de cada ítem del checklist para un curso.
     *
     * @return array<string,string> keys: actas, guias, presentaciones, trabajos, finales => 'cumplido'|'pendiente'
     */
    public function statusForCourse(int $courseId): array
    {
        /** @var Curso $curso */
        $curso = Curso::with(['evidencias', 'actas', 'registroNotas', 'informeFinal'])->findOrFail($courseId);

        $config = config('requirements');
        $mod = strtolower((string) $curso->modalidad);
        $modeKey = str_contains($mod, 'semi') ? 'semipresencial' : 'presencial';
        $modeCfg = $config[$modeKey] ?? $config['presencial'];

        $requiredActas = (int) ($modeCfg['acta'] ?? 0);
        $requiredGuias = 0;
        $requiredPresent = 0;
        $requiredTrabajos = 0;

        $requiredGuias += (int) ($modeCfg['guia'] ?? 0);
        $requiredPresent += (int) ($modeCfg['presentacion'] ?? 0);
        $requiredTrabajos += (int) ($modeCfg['trabajo'] ?? 0);

        if (isset($modeCfg['per_block'])) {
            foreach ($modeCfg['per_block'] as $blockCfg) {
                $requiredGuias += (int) ($blockCfg['guia'] ?? 0);
                $requiredPresent += (int) ($blockCfg['presentacion'] ?? 0);
                $requiredTrabajos += (int) ($blockCfg['trabajo'] ?? 0);
            }
        }

        $finalesCfg = $modeCfg['finales'] ?? [];

        // Hechos
        $actasDone = (int) $curso->actas->count();
        $evCounts = $curso->evidencias->groupBy('tipo')->map->count();

        $guiasDone = (int) ($evCounts['guia'] ?? 0);
        $presentDone = (int) ($evCounts['presentacion'] ?? 0);
        $trabajosDone = (int) ($evCounts['trabajo'] ?? 0);

        $items = [];
        $items['actas'] = ($requiredActas > 0 && $actasDone >= $requiredActas) ? 'cumplido' : 'pendiente';
        $items['guias'] = ($requiredGuias > 0 && $guiasDone >= $requiredGuias) ? 'cumplido' : 'pendiente';
        $items['presentaciones'] = ($requiredPresent > 0 && $presentDone >= $requiredPresent) ? 'cumplido' : 'pendiente';
        $items['trabajos'] = ($requiredTrabajos > 0 && $trabajosDone >= $requiredTrabajos) ? 'cumplido' : 'pendiente';

        // Finales
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

        $items['finales'] = ($finalRequired > 0 && $finalDone >= $finalRequired) ? 'cumplido' : 'pendiente';

        return $items;
    }
}

