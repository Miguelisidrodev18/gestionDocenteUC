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
        'modalidad',
        'image_url',
        'docente_id',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function docente()
    {
        return $this->belongsTo(\App\Models\Docente::class);
    }
};
0