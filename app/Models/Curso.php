<?php

namespace App\Models;

use App\Services\ProgressService;
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
        'sede_id',
        'campus_id',
        'area_id',
        'periodo',
        'periodo_id',
        'periodo_academico',
        'avance_cache',
        'review_state',
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

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function areaCatalogo(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function periodoAcademicoRel(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
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

    public function assignment(): HasOne
    {
        return $this->hasOne(Assignment::class);
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
        $cfg = config('requirements');
        $mod = strtolower((string) $this->modalidad);
        $mode = str_contains($mod, 'semi') ? 'semipresencial' : 'presencial';
        $modeCfg = $cfg[$mode] ?? $cfg['presencial'];

        $req = [];

        // Actas de reunión
        $actaRequired = (int) ($modeCfg['acta'] ?? 0);
        if ($actaRequired > 0) {
            $req['acta'] = ['required' => $actaRequired, 'max' => $actaRequired];
        }

        // Guias / presentaciones / trabajos (sumando por bloque si aplica)
        $guiaRequired = (int) ($modeCfg['guia'] ?? 0);
        $presentRequired = (int) ($modeCfg['presentacion'] ?? 0);
        $trabajoRequired = (int) ($modeCfg['trabajo'] ?? 0);

        if (isset($modeCfg['per_block'])) {
            foreach ($modeCfg['per_block'] as $blockCfg) {
                $guiaRequired += (int) ($blockCfg['guia'] ?? 0);
                $presentRequired += (int) ($blockCfg['presentacion'] ?? 0);
                $trabajoRequired += (int) ($blockCfg['trabajo'] ?? 0);
            }
        }

        if ($guiaRequired > 0) {
            $req['guia'] = ['required' => $guiaRequired, 'max' => null];
        }
        if ($presentRequired > 0) {
            $req['presentacion'] = ['required' => $presentRequired, 'max' => $presentRequired];
        }
        if ($trabajoRequired > 0) {
            $req['trabajo'] = ['required' => $trabajoRequired, 'max' => $trabajoRequired];
        }

        // Documentos finales: acta_final, registro, informe_final
        $finales = $modeCfg['finales'] ?? [];
        foreach ($finales as $key => $required) {
            $required = (int) $required;
            if ($required <= 0) {
                continue;
            }
            $req[$key] = ['required' => $required, 'max' => $required];
        }

        return $req;
    }

    public function getAvanceAttribute(): int
    {
        $cached = $this->attributes['avance_cache'] ?? null;
        if ($cached !== null) {
            return (int) $cached;
        }

        return app(ProgressService::class)->recomputeForCourse($this->id);
    }
};
