<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    use SoftDeletes;

    protected $table = 'campus';

    protected $fillable = [
        'sede_id',
        'codigo',
        'nombre',
        'activo',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }
}

