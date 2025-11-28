<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalResult extends Model
{
    protected $fillable = [
        'curso_id',
        'sede',
        'aprobados',
        'desaprobados',
        'promedio',
        'avance_promedio',
        'periodo',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }
}
