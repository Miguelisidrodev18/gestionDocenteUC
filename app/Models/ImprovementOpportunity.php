<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImprovementOpportunity extends Model
{
    protected $fillable = [
        'curso_id',
        'sede',
        'descripcion',
        'owner_user_id',
        'due_date',
        'status',
        'evidencia_path',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

