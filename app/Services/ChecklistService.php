<?php

namespace App\Services;

use App\Models\Curso;
use App\Models\TipoEvidencia;

class ChecklistService
{
    /**
     * Devuelve el estado de cada ítem del checklist para un curso.
     *
     * @return array<string,string> keys: códigos de tipos de evidencia => 'cumplido'|'pendiente'
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
        $evCounts = $curso->evidencias
            ->where('estado', 'validado')
            ->groupBy('tipo')
            ->map->count();

        $guiasDone = (int) ($evCounts['guia'] ?? 0);
        $presentDone = (int) ($evCounts['presentacion'] ?? 0);
        $trabajosDone = (int) ($evCounts['trabajo'] ?? 0);

        $computed = [];
        $computed['acta'] = ($requiredActas > 0 && $actasDone >= $requiredActas) ? 'cumplido' : 'pendiente';
        $computed['guia'] = ($requiredGuias > 0 && $guiasDone >= $requiredGuias) ? 'cumplido' : 'pendiente';
        $computed['presentacion'] = ($requiredPresent > 0 && $presentDone >= $requiredPresent) ? 'cumplido' : 'pendiente';
        $computed['trabajo'] = ($requiredTrabajos > 0 && $trabajosDone >= $requiredTrabajos) ? 'cumplido' : 'pendiente';

        // Finales
        $finalRequired = 0;
        $finalDone = 0;
        $hasRegistro = false;
        $hasInforme = false;
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
                    $hasRegistro = $curso->registroNotas && $curso->registroNotas->count() > 0;
                    $has = $hasRegistro;
                    break;
                case 'informe_final':
                    $hasInforme = (bool) $curso->informeFinal;
                    $has = $hasInforme;
                    break;
            }
            if ($has) {
                $finalDone++;
            }
        }

        if (($finalesCfg['registro'] ?? 0) > 0) {
            $computed['registro'] = $hasRegistro ? 'cumplido' : 'pendiente';
        }
        if (($finalesCfg['informe_final'] ?? 0) > 0) {
            $computed['informe_final'] = $hasInforme ? 'cumplido' : 'pendiente';
        }

        // Status por tipo de evidencia existente en catálogo
        $items = [];
        $tipos = TipoEvidencia::orderBy('codigo')->get(['codigo']);
        foreach ($tipos as $tipo) {
            $code = (string) $tipo->codigo;
            $items[$code] = $computed[$code] ?? 'pendiente';
        }

        $manual = $curso->checklist_manual ?? [];
        if (is_array($manual) && ! empty($manual)) {
            $legacyMap = [
                'actas' => 'acta',
                'guias' => 'guia',
                'presentaciones' => 'presentacion',
                'trabajos' => 'trabajo',
            ];
            foreach ($legacyMap as $legacy => $code) {
                if (isset($manual[$legacy]) && ! isset($manual[$code])) {
                    $manual[$code] = $manual[$legacy];
                }
            }
            if (isset($manual['finales'])) {
                if (! isset($manual['registro'])) {
                    $manual['registro'] = $manual['finales'];
                }
                if (! isset($manual['informe_final'])) {
                    $manual['informe_final'] = $manual['finales'];
                }
            }

            foreach ($items as $code => $value) {
                if (isset($manual[$code]) && in_array($manual[$code], ['cumplido', 'pendiente'], true)) {
                    $items[$code] = $manual[$code];
                }
            }
        }

        return $items;
    }
}
