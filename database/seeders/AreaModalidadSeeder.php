<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Modalidad;
use Illuminate\Database\Seeder;

class AreaModalidadSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'Computación y TI',
            'Ingeniería de Software',
            'Proyectos',
            'Sistemas de Información',
        ];

        foreach ($areas as $nombre) {
            $area = Area::firstOrCreate(['nombre' => $nombre]);
            Modalidad::firstOrCreate(['nombre' => 'Presencial', 'area_id' => $area->id], ['duracion_semanas' => 16]);
            Modalidad::firstOrCreate(['nombre' => 'Semipresencial', 'area_id' => $area->id], ['duracion_semanas' => 8]);
        }
    }
}

