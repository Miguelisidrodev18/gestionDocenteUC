<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\RegistroNota;
use Illuminate\Http\Request;

class RegistroNotasController extends Controller
{
    public function store(Request $request, Curso $curso)
    {
        if (! $curso->userCanUpload($request->user())) {
            abort(403, 'No puedes registrar notas para este curso.');
        }

        $data = $request->validate([
            'campus' => 'nullable|string|max:100',
            'nrc' => 'nullable|string|max:100',
            'docente_nombre' => 'nullable|string|max:150',
            'total_estudiantes' => 'required|integer|min:0',
            'c1_aprobados' => 'required|integer|min:0',
            'c1_desaprobados' => 'required|integer|min:0',
            'c1_promedio' => 'required|numeric|min:0',
            'ep_aprobados' => 'required|integer|min:0',
            'ep_desaprobados' => 'required|integer|min:0',
            'ep_promedio' => 'required|numeric|min:0',
            'hipotesis_c1' => 'nullable|string',
            'hipotesis_ep' => 'nullable|string',
        ]);

        $registro = new RegistroNota($data);
        $registro->curso_id = $curso->id;
        $registro->docente_id = $request->user()->docente->id ?? null;
        $registro->created_by = $request->user()->id;
        $registro->save();

        return back()->with('success', 'Registro de notas guardado.');
    }

    public function destroy(Request $request, RegistroNota $registroNota)
    {
        $curso = $registroNota->curso;
        if (! $curso || ! $curso->userCanUpload($request->user())) {
            abort(403, 'No puedes eliminar este registro.');
        }
        $registroNota->delete();
        return back()->with('success', 'Registro eliminado');
    }
}

