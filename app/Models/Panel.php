<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Panel extends Model
{
    protected $fillable = [
        'curso_id',
        'type',
        'scheduled_at',
        'location',
        'status',
        'max_members',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AdvisorAssignment::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PanelLog::class);
    }
}

