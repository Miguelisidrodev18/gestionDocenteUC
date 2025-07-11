<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
class CursoController extends Controller
{
    public function index()
    {
        $cursos = \App\Models\Curso::with('materiales')->get();
        return Inertia::render('Cursos/index', [
            'cursos' => $cursos
        ]);
    }
}
