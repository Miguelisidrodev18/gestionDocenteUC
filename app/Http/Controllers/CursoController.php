<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
class CursoController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->get('periodo', '2025-2');
        $docenteFilter = $request->get('docente_id');
        $user = $request->user();

        $cursosQuery = Curso::with(['docente', 'responsable'])
            ->where('periodo', $periodo);

        if ($user) {
            if ($user->isAdmin()) {
                // Admin ve todos; si hay filtro por docente, aplicarlo
                if ($docenteFilter) {
                    $cursosQuery->where('docente_id', $docenteFilter);
                }
            } elseif ($user->isResponsable()) {
                $cursosQuery->where('user_id', $user->id);
            } else {
                $docenteId = $user->docente?->id;
                $cursosQuery->where(function ($query) use ($user, $docenteId) {
                    $query->where('user_id', $user->id);

                    if ($docenteId) {
                        $query->orWhere('docente_id', $docenteId);
                    }
                });
            }
        }

        $cursos = $cursosQuery->get();
        $docentes = Docente::all();
        $responsables = User::whereIn('role', ['responsable', 'admin'])->get();

        return Inertia::render('Cursos/Index', [
            'cursos' => $cursos,
            'docentes' => $docentes,
            'responsables' => $responsables,
            'periodo' => $periodo,
            'filters' => [
                'docente_id' => $docenteFilter,
            ],
            'currentDocenteId' => $user?->docente?->id,
            'currentUserRole' => $user?->role,
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
        'periodo' => 'required|string',
        'responsable_id' => 'nullable|exists:users,id',
    ]);

    Curso::create([
        'nombre' => $request->nombre,
        'codigo' => $request->codigo,
        'descripcion' => $request->descripcion,
        'creditos' => $request->creditos,
        'nivel' => $request->nivel,
        'modalidad' => $request->modalidad,
        'docente_id' => $request->docente_id,
        'drive_url' => $request->drive_url,
        'user_id' => $request->responsable_id ?? $request->user()->id,
        'periodo' => $request->periodo,
    ]);

    return redirect()->route('cursos.index')->with('success', 'Curso creado correctamente');
}
public function edit($id)
{
    $curso = Curso::with(['docente', 'responsable'])->findOrFail($id);
    $docentes = Docente::all();
    $responsables = User::where('role', 'responsable')->get();

    return Inertia::render('Cursos/Edit', [
        'curso' => $curso,
        'docentes' => $docentes,
        'responsables' => $responsables,
    ]);
}
public function destroy($id)
{
    $curso = Curso::findOrFail($id);
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
        'docente_id' => 'required|exists:docentes,id',
        'drive_url' => 'nullable|url',
        'responsable_id' => 'nullable|exists:users,id',
    ]);

    $curso = Curso::findOrFail($id);
    $curso->update([
        'nombre' => $request->nombre,
        'codigo' => $request->codigo,
        'descripcion' => $request->descripcion,
        'creditos' => $request->creditos,
        'nivel' => $request->nivel,
        'modalidad' => $request->modalidad,
        'docente_id' => $request->docente_id,
        'drive_url' => $request->drive_url,
        'user_id' => $request->responsable_id ?? $curso->user_id,
    ]);

    return redirect()->route('cursos.index')->with('success', 'Curso actualizado correctamente');
}
}
