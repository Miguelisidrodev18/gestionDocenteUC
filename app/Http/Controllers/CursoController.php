<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
class CursoController extends Controller
{
    public function index()
    {
        $cursos = \App\Models\Curso::where('user_id', auth()->id())->with('docente')->get();
        $docentes = \App\Models\Docente::all();
        return Inertia::render('Cursos/Index', [
            'cursos' => $cursos,
            'docentes' => $docentes,
        ]);
    }
    public function store(Request $request)
{
    $request->validate([
        'nombre' => 'required|string|max:150',
        'codigo' => 'required|string|unique:cursos,codigo',
        'descripcion' => 'nullable|string',
        'creditos' => 'required|integer',
        'nivel' => 'required|in:pregrado,postgrado',
        'modalidad' => 'required|string',
        'image_url' => 'nullable|string',
        'docente_id' => 'required|exists:docentes,id',
    ]);

    \App\Models\Curso::create([
        'nombre' => $request->nombre,
        'codigo' => $request->codigo,
        'descripcion' => $request->descripcion,
        'creditos' => $request->creditos,
        'nivel' => $request->nivel,
        'modalidad' => $request->modalidad,
        'image_url' => $request->image_url,
        'docente_id' => $request->docente_id,
        'user_id' => auth()->id(),
    ]);

    return redirect()->route('cursos.index')->with('success', 'Curso creado correctamente');
}
}
