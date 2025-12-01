<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Update extends Model
{
    use SoftDeletes;

    public const AUDIENCE_TODOS = 'TODOS';
    public const AUDIENCE_DOCENTES = 'DOCENTES';
    public const AUDIENCE_RESPONSABLES = 'RESPONSABLES';
    public const AUDIENCE_ADMIN = 'ADMIN';

    protected $fillable = [
        'titulo',
        'cuerpo_md',
        'audience',
        'pinned',
        'starts_at',
        'ends_at',
        'creado_por',
    ];

    protected $casts = [
        'pinned' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function reads(): HasMany
    {
        return $this->hasMany(UpdateRead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function scopeVisibleFor($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('audience', self::AUDIENCE_TODOS);

            if ($user->isDocente()) {
                $q->orWhere('audience', self::AUDIENCE_DOCENTES);
            }

            if ($user->isResponsable()) {
                $q->orWhere('audience', self::AUDIENCE_RESPONSABLES);
            }

            if ($user->isAdmin()) {
                $q->orWhere('audience', self::AUDIENCE_ADMIN);
            }
        });
    }

    public function isActiveNow(): bool
    {
        $now = now();

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        return true;
    }
}
