<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Seeder;

class EspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Ingenieria de Sistemas',
            'Ingenieria de Sistemas e Informatica',
            'Ingenieria Informatica',
            'Ingenieria de Software',
            'Ciencias de la Computacion',
            'Tecnologias de la Informacion',
            'Redes y Comunicaciones',
            'Ciberseguridad',
            'Data Science',
            'Inteligencia Artificial',
        ];

        foreach ($items as $nombre) {
            Especialidad::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
