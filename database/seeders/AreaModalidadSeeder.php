<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Modalidad;
use Illuminate\Database\Seeder;

class AreaModalidadSeeder extends Seeder
{
    public function run(): void
    {
        $areas = Area::all();
        if ($areas->isEmpty()) {
            $areas = collect([
                'Computacion y TI',
                'Ingenieria de Software',
                'Proyectos',
                'Sistemas de Informacion',
            ])->map(fn ($nombre) => Area::firstOrCreate(['nombre' => $nombre]));
        }

        $modalidades = [
            [
                'nombre' => 'Presencial',
                'duracion_semanas' => 16,
                'estructura_duracion' => 'CONTINUA',
                'num_bloques' => null,
                'semanas_por_bloque' => null,
            ],
            [
                'nombre' => 'Semipresencial bloque A',
                'duracion_semanas' => 8,
                'estructura_duracion' => 'BLOQUES',
                'num_bloques' => 2,
                'semanas_por_bloque' => 8,
            ],
            [
                'nombre' => 'Semipresencial bloque B',
                'duracion_semanas' => 8,
                'estructura_duracion' => 'BLOQUES',
                'num_bloques' => 2,
                'semanas_por_bloque' => 8,
            ],
        ];

        foreach ($areas as $area) {
            foreach ($modalidades as $data) {
                Modalidad::firstOrCreate(
                    ['nombre' => $data['nombre'], 'area_id' => $area->id],
                    [
                        'duracion_semanas' => $data['duracion_semanas'],
                        'estructura_duracion' => $data['estructura_duracion'],
                        'num_bloques' => $data['num_bloques'],
                        'semanas_por_bloque' => $data['semanas_por_bloque'],
                    ],
                );
            }
        }
    }
}
