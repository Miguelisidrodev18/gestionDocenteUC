<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidencia extends Model
{
    protected $fillable = [
        'curso_id',
        'user_id',
        'tipo',
        'archivo_path',
        'semana',
        'fecha_subida',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_subida' => 'datetime',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

