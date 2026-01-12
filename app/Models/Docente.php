<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Importa el trait HasFactory
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'cv_docente',
        'cul',
        'linkedin',
        'estado',
        'cip',
        'user_id',
    ];

    protected $appends = [
        'cv_docente',
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

    public function cvDocuments(): HasMany
    {
        return $this->hasMany(CvDocument::class);
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

    public function advisorProfile(): HasOne
    {
        return $this->hasOne(AdvisorProfile::class);
    }

    public function getCvDocenteAttribute(): ?string
    {
        return $this->cv_personal;
    }

    public function setCvDocenteAttribute(?string $value): void
    {
        $this->attributes['cv_personal'] = $value;
    }
}
