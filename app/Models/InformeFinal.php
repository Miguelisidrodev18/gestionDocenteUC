<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformeFinal extends Model
{
    protected $table = 'informe_finals';
    protected $fillable = [
        'curso_id','responsable','fecha_presentacion','resultados','mejoras','created_by'
    ];

    protected $casts = [
        'fecha_presentacion' => 'date',
        'resultados' => 'array',
        'mejoras' => 'array',
    ];

    public function curso(): BelongsTo { return $this->belongsTo(Curso::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
}
