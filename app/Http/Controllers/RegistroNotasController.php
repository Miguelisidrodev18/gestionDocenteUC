<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\RegistroNota;
use App\Events\DocumentUpdated;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistroNotasController extends Controller
{
    public function store(Request $request, Curso $curso)
    {
        $this->authorize('create', [RegistroNota::class, $curso]);

        $data = $request->validate([
            'campus' => 'nullable|string|max:100',
            'campus_id' => 'nullable|integer',
            'nrc' => 'nullable|string|max:100',
            'corte' => ['required', Rule::in(['PARCIAL', 'CONSOLIDADO2', 'FINAL'])],
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

        // Validación de unicidad por curso/campus_id/nrc/corte
        $uniqueRule = Rule::unique('registro_notas')
            ->where('curso_id', $curso->id)
            ->where('campus_id', $data['campus_id'] ?? null)
            ->where('nrc', $data['nrc'] ?? null)
            ->where('corte', $data['corte']);

        $request->validate([
            'nrc' => [$uniqueRule],
        ], [
            'nrc.unique' => 'Ya existe un registro para este curso, campus, NRC y corte.',
        ]);

        $registro = new RegistroNota($data);
        $registro->curso_id = $curso->id;
        $registro->docente_id = $request->user()->docente->id ?? null;
        $registro->created_by = $request->user()->id;
        $registro->save();

        event(new DocumentUpdated($curso->id));

        return back()->with('success', 'Registro de notas guardado.');
    }

    public function destroy(Request $request, RegistroNota $registroNota)
    {
        $this->authorize('update', $registroNota);
        $cursoId = $registroNota->curso_id;
        $registroNota->delete();
        event(new DocumentUpdated($cursoId));
        return back()->with('success', 'Registro eliminado');
    }
}
