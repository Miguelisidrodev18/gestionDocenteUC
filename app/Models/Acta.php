<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acta extends Model
{
    protected $fillable = [
        'curso_id',
        'numero',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'modalidad',
        'responsable',
        'asistentes',
        'acuerdos',
        'created_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'asistentes' => 'array',
        'acuerdos' => 'array',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

