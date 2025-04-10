<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Importa el trait HasFactory
use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'email',
        'telefono',
        'especialidad',
        'cv_sunedu',
        'cv_personal',
        'linkedin',
        'estado',
    ];

    /**
     * Get the cursos for the docente
     */
    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    /**
     * Get the actualizaciones for the docente
     */
    public function actualizaciones(): HasMany
    {
        return $this->hasMany(Actualizacion::class);
    }
}
