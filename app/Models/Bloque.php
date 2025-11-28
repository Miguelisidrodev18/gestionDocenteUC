<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bloque extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'codigo',
        'nombre',
        'semanas',
        'activo',
    ];

    protected $casts = [
        'semanas' => 'integer',
        'activo' => 'boolean',
    ];

    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoModalidad::class);
    }
}

