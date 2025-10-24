<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Importa el trait HasFactory
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'cip',
        'user_id',
    ];

    /**
     * Get the cursos for the docente
     */
    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    public function cursosColabora(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class, 'curso_docente');
    }

    /**
     * Get the actualizaciones for the docente
     */
    public function actualizaciones(): HasMany
    {
        return $this->hasMany(Actualizacion::class);
    }
    
    /**
     * Get the user that owns the docente
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
