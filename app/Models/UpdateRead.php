<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdateRead extends Model
{
    protected $fillable = [
        'update_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function update(): BelongsTo
    {
        return $this->belongsTo(Update::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

