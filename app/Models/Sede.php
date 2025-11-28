<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
    ];

    public function campus(): HasMany
    {
        return $this->hasMany(Campus::class);
    }
}

