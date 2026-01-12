<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\RegistroNota;
use App\Events\DocumentUpdated;
use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistroNotasController extends Controller
{
    public function store(Request $request, Curso $curso)
    {
        $this->authorize('create', [RegistroNota::class, $curso]);

        $data = $request->validate([
            'campus' => 'nullable|string|max:100',
            'sede_id' => 'nullable|exists:sedes,id',
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

        if (! empty($data['sede_id']) && empty($data['campus'])) {
            $sede = Sede::find($data['sede_id']);
            if ($sede) {
                $data['campus'] = $sede->nombre;
            }
        }

        // Validación de unicidad por curso/sede_id/nrc/corte
        $uniqueRule = Rule::unique('registro_notas')
            ->where('curso_id', $curso->id)
            ->where('sede_id', $data['sede_id'] ?? null)
            ->where('nrc', $data['nrc'] ?? null)
            ->where('corte', $data['corte']);

        $request->validate([
            'nrc' => [$uniqueRule],
        ], [
            'nrc.unique' => 'Ya existe un registro para este curso, sede, NRC y corte.',
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
