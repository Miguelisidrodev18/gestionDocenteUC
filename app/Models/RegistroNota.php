<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroNota extends Model
{
    protected $fillable = [
        'curso_id','docente_id','campus','nrc','docente_nombre','total_estudiantes',
        'c1_aprobados','c1_desaprobados','c1_promedio',
        'ep_aprobados','ep_desaprobados','ep_promedio',
        'hipotesis_c1','hipotesis_ep','created_by',
    ];

    protected $casts = [
        'c1_promedio' => 'float',
        'ep_promedio' => 'float',
    ];

    public function curso(): BelongsTo { return $this->belongsTo(Curso::class); }
    public function docente(): BelongsTo { return $this->belongsTo(Docente::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}

