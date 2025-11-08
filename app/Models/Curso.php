<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Curso extends Model
{
    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'creditos',
        'nivel',
        'modalidad',
        'modalidad_id',
        'docente_id',
        'drive_url',
        'user_id',
        'periodo',
        'periodo_academico',
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
    
    public function evidencias(): HasMany
    {
        return $this->hasMany(Evidencia::class);
    }

    public function modalidadRel(): BelongsTo
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id');
    }

    public function actas(): HasMany
    {
        return $this->hasMany(Acta::class);
    }

    public function registroNotas(): HasMany
    {
        return $this->hasMany(RegistroNota::class);
    }

    public function informeFinal(): HasOne
    {
        return $this->hasOne(InformeFinal::class);
    }

    public function docentesParticipantes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'curso_docente');
    }

    public function userCanUpload($user): bool
    {
        if (! $user) return false;
        if ($user->isAdmin()) return true;
        if ($this->user_id === $user->id) return true; // responsable
        $docenteId = $user->docente?->id;
        if (! $docenteId) return false;
        if ($this->docente_id === $docenteId) return true; // principal
        return $this->docentesParticipantes()->where('docente_id', $docenteId)->exists();
    }

    public function requerimientos(): array
    {
        $semanas = $this->modalidadRel?->duracion_semanas;
        if (! $semanas) {
            $mod = strtolower((string) $this->modalidad);
            $semanas = str_contains($mod, 'semi') ? 8 : 16;
        }

        return [
            'acta' => ['required' => 2, 'max' => 2],
            'guia' => ['required' => 6, 'max' => null],
            'presentacion' => ['required' => $semanas, 'max' => $semanas],
            'trabajo' => ['required' => 3, 'max' => 3],
            'excel' => ['required' => 1, 'max' => 1],
        ];
    }

    public function getAvanceAttribute(): int
    {
        $req = $this->requerimientos();
        $counts = $this->evidencias()->selectRaw("tipo, COUNT(*) as total")->groupBy('tipo')->pluck('total','tipo');
        $actaCount = (int) $this->actas()->count();
        $totalReq = 0; $cumplidos = 0;
        foreach ($req as $tipo => $data) {
            $required = (int) ($data['required'] ?? 0);
            $totalReq += $required;
            $have = $tipo === 'acta' ? $actaCount : (int) ($counts[$tipo] ?? 0);
            $cumplidos += min($have, $required);
        }
        if ($totalReq === 0) return 0;
        return (int) round(($cumplidos / $totalReq) * 100);
    }
};
