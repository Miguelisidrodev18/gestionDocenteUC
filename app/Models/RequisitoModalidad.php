<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitoModalidad extends Model
{
    protected $table = 'requisitos_modalidad';

    protected $fillable = [
        'modalidad_id',
        'tipo_evidencia_id',
        'aplica_a',
        'bloque_id',
        'minimo',
        'peso',
    ];

    protected $casts = [
        'minimo' => 'integer',
        'peso' => 'integer',
    ];

    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(Modalidad::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoEvidencia::class, 'tipo_evidencia_id');
    }

    public function bloque(): BelongsTo
    {
        return $this->belongsTo(Bloque::class);
    }
}
