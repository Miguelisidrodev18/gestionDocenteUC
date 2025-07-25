<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'user_id',
        // otros campos...
    ];
}
