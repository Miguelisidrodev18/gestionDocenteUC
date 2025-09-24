<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'creditos',
        'nivel',
        'modalidad',
        'docente_id',
        'drive_url',
        'user_id',
        'periodo',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CourseDocument::class);
    }
};
