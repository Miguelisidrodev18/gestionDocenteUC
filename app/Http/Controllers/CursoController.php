<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
class CursoController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->get('periodo', '2025-2'); // Obtén el período de la solicitud, por defecto '2025-2'
        $cursos = \App\Models\Curso::where('user_id', auth()->id())
            ->where('periodo', $periodo)
            ->with('docente')
            ->get();
        $docentes = \App\Models\Docente::all();

        return Inertia::render('Cursos/Index', [
            'cursos' => $cursos,
            'docentes' => $docentes,
            'periodo' => $periodo, // Envía el período actual
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
        'docente_id' => 'required|exists:docentes,id',
        'drive_url' => 'nullable|url',
        'periodo' => 'required|string', // Validación para el período
    ]);

    \App\Models\Curso::create([
        'nombre' => $request->nombre,
        'codigo' => $request->codigo,
        'descripcion' => $request->descripcion,
        'creditos' => $request->creditos,
        'nivel' => $request->nivel,
        'modalidad' => $request->modalidad,
        'docente_id' => $request->docente_id,
        'drive_url' => $request->drive_url,
        'user_id' => auth()->id(),
        'periodo' => $request->periodo,
    ]);

    return redirect()->route('cursos.index')->with('success', 'Curso creado correctamente');
}
public function edit($id)
{
    $curso = \App\Models\Curso::findOrFail($id);
    $docentes = \App\Models\Docente::all();

    return Inertia::render('Cursos/Edit', [
        'curso' => $curso,
        'docentes' => $docentes,
    ]);
}
public function destroy($id)
{
    $curso = \App\Models\Curso::findOrFail($id);
    $curso->delete();

    return redirect()->route('cursos.index')->with('success', 'Curso eliminado correctamente');
}
public function update(Request $request, $id)
{
    $request->validate([
        'nombre' => 'required|string|max:150',
        'codigo' => 'required|string|unique:cursos,codigo,' . $id,
        'descripcion' => 'nullable|string',
        'creditos' => 'required|integer',
        'nivel' => 'required|in:pregrado,postgrado',
        'modalidad' => 'required|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'docente_id' => 'required|exists:docentes,id',
        'drive_url' => 'nullable|url',
    ]);

    $curso = \App\Models\Curso::findOrFail($id);

    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('cursos', 'public');
        $curso->image_url = $imagePath;
    }

    $curso->update($request->except('image'));

    return redirect()->route('cursos.index')->with('success', 'Curso actualizado correctamente');
}
}
