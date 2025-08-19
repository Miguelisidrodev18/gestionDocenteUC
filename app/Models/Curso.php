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
        'docente_id',
        'drive_url',
        'user_id',
        'periodo',
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
