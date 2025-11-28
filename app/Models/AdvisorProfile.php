<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorProfile extends Model
{
    protected $fillable = [
        'docente_id',
        'current_load',
        'max_load',
        'main_area',
        'expertise',
        'experience',
        'notes',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }
}

