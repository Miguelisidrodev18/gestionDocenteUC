<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'creditos',
        'nivel',
    ];
};
