<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $fillable = [
        'curso_id',
        'responsable_user_id',
        'sede_id',
        'modalidad_docente',
        'assigned_at',
        'email_sent_at',
        'status',
        'invited_by',
        'responded_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AssignmentLog::class);
    }
}
