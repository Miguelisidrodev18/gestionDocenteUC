<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoAcademico extends Model
{
    protected $table = 'periodos_academicos';

    protected $fillable = [
        'codigo',
        'inicio',
        'fin',
        'estado',
    ];

    protected $casts = [
        'inicio' => 'date',
        'fin' => 'date',
    ];

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class, 'periodo_id');
    }
}

