<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sede extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
    ];

    // Campus eliminado; sede no tiene relación hija adicional.
}
