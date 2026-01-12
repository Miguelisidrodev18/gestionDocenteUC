<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modalidad extends Model
{
    use SoftDeletes;

    protected $table = 'modalidades';
    protected $fillable = [
        'codigo',
        'nombre',
        'duracion_semanas',
        'estructura_duracion',
        'num_bloques',
        'semanas_por_bloque',
        'bloque_id',
        'area_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'num_bloques' => 'integer',
        'semanas_por_bloque' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function bloque(): BelongsTo
    {
        return $this->belongsTo(Bloque::class);
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoModalidad::class);
    }
}
