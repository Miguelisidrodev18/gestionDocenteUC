<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelLog extends Model
{
    protected $fillable = [
        'panel_id',
        'user_id',
        'action',
        'details',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

