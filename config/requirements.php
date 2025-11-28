<?php

return [
    // Pesos por grupo de requisitos (deben sumar 1.0 idealmente)
    'weights' => [
        'actas' => 0.30,
        'guias' => 0.20,
        'presentaciones' => 0.30,
        'trabajos' => 0.10,
        'finales' => 0.10,
    ],

    // Requerimientos para cursos presenciales
    'presencial' => [
        'acta' => 3,
        'guia' => 6,
        'presentacion' => 16,
        'trabajo' => 3,
        'finales' => [
            'acta_final' => 1,
            'registro' => 1,
            'informe_final' => 1,
        ],
    ],

    // Requerimientos para cursos semipresenciales (bloques A y B, 8 semanas cada uno)
    'semipresencial' => [
        'acta' => 3,
        'per_block' => [
            'A' => [
                'semanas' => 8,
                'guia' => 3,
                'presentacion' => 8,
                'trabajo' => 2,
            ],
            'B' => [
                'semanas' => 8,
                'guia' => 3,
                'presentacion' => 8,
                'trabajo' => 1,
            ],
        ],
        'finales' => [
            'acta_final' => 1,
            'registro' => 1,
            'informe_final' => 1,
        ],
    ],
];

