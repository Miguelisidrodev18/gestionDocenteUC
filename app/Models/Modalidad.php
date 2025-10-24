<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modalidad extends Model
{
    protected $table = 'modalidades';
    protected $fillable = ['nombre', 'duracion_semanas', 'area_id'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }
}
