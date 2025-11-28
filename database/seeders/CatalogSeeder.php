<?php

namespace Database\Seeders;

use App\Models\Bloque;
use App\Models\Modalidad;
use App\Models\TipoEvidencia;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Modalidades básicas (si ya existen, solo completar código/activo)
        $modalidades = [
            ['codigo' => 'PRES', 'nombre' => 'Presencial', 'duracion_semanas' => 16],
            ['codigo' => 'DIST', 'nombre' => 'Distancia', 'duracion_semanas' => 16],
            ['codigo' => 'SEMI', 'nombre' => 'Semipresencial', 'duracion_semanas' => 16],
        ];

        foreach ($modalidades as $data) {
            $modalidad = Modalidad::firstOrCreate(
                ['codigo' => $data['codigo']],
                [
                    'nombre' => $data['nombre'],
                    'duracion_semanas' => $data['duracion_semanas'],
                    // área y activo pueden ajustarse luego desde Catálogos
                    'area_id' => Modalidad::first()->area_id ?? 1,
                    'activo' => true,
                ],
            );

            if (! $modalidad->codigo) {
                $modalidad->codigo = $data['codigo'];
                $modalidad->save();
            }
        }

        // Tipos de evidencia
        $tipos = [
            ['codigo' => 'acta', 'nombre' => 'Acta de reunión'],
            ['codigo' => 'guia', 'nombre' => 'Guía'],
            ['codigo' => 'presentacion', 'nombre' => 'Presentación'],
            ['codigo' => 'trabajo', 'nombre' => 'Trabajo'],
            ['codigo' => 'registro', 'nombre' => 'Registro de notas'],
            ['codigo' => 'informe_final', 'nombre' => 'Informe final'],
        ];

        foreach ($tipos as $t) {
            TipoEvidencia::firstOrCreate(
                ['codigo' => $t['codigo']],
                [
                    'nombre' => $t['nombre'],
                    'cuenta_en_avance' => true,
                    'activo' => true,
                ],
            );
        }

        // Bloques A y B
        Bloque::firstOrCreate(
            ['codigo' => 'A'],
            ['nombre' => 'Bloque A', 'semanas' => 8, 'activo' => true],
        );

        Bloque::firstOrCreate(
            ['codigo' => 'B'],
            ['nombre' => 'Bloque B', 'semanas' => 8, 'activo' => true],
        );
    }
}

