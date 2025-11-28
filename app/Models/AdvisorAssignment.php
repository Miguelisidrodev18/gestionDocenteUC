<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorAssignment extends Model
{
    protected $fillable = [
        'panel_id',
        'docente_id',
        'role',
        'status',
        'invited_by',
        'responded_at',
        'remarks',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}

