<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoEvidencia extends Model
{
    use SoftDeletes;

    protected $table = 'tipos_evidencia';

    protected $fillable = [
        'codigo',
        'nombre',
        'cuenta_en_avance',
        'activo',
    ];

    protected $casts = [
        'cuenta_en_avance' => 'boolean',
        'activo' => 'boolean',
    ];

    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoModalidad::class, 'tipo_evidencia_id');
    }
}

