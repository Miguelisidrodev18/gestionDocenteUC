<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\Curso;
use Illuminate\Http\Request;

class ActaController extends Controller
{
    /** Store an acta (meeting minutes) for a course. */
    public function store(Request $request, Curso $curso)
    {
        $this->authorize('create', [Acta::class, $curso]);

        $data = $request->validate([
            'numero' => 'nullable|string|max:20',
            'fecha' => 'required|date',
            'hora_inicio' => 'nullable|string|max:20',
            'hora_fin' => 'nullable|string|max:20',
            'modalidad' => 'nullable|string|max:50',
            'responsable' => 'nullable|string|max:150',
            'asistentes' => 'nullable|array',
            'asistentes.*.nombre' => 'nullable|string|max:150',
            'asistentes.*.campus' => 'nullable|string|max:100',
            'asistentes.*.asistio' => 'nullable|boolean',
            'acuerdos' => 'nullable|array',
            'acuerdos.*.tema' => 'nullable|string|max:200',
            'acuerdos.*.acuerdo' => 'nullable|string|max:3000',
            'acuerdos.*.responsable' => 'nullable|string|max:150',
            'acuerdos.*.fecha_entrega' => 'nullable|string|max:50',
        ]);

        $acta = new Acta($data);
        $acta->curso_id = $curso->id;
        $acta->created_by = $request->user()->id;
        $acta->save();

        event(new \App\Events\DocumentUpdated($curso->id));

        return back()->with('success', 'Acta registrada correctamente.');
    }

    /** Update an Acta minimal fields */
    public function update(Request $request, Acta $acta)
    {
        $this->authorize('update', $acta);

        $data = $request->validate([
            'numero' => 'nullable|string|max:20',
            'fecha' => 'nullable|date',
            'hora_inicio' => 'nullable|string|max:20',
            'hora_fin' => 'nullable|string|max:20',
            'modalidad' => 'nullable|string|max:50',
            'responsable' => 'nullable|string|max:150',
            'asistentes' => 'nullable|array',
            'asistentes.*.nombre' => 'nullable|string|max:150',
            'asistentes.*.campus' => 'nullable|string|max:100',
            'asistentes.*.asistio' => 'nullable|boolean',
            'acuerdos' => 'nullable|array',
            'acuerdos.*.tema' => 'nullable|string|max:200',
            'acuerdos.*.acuerdo' => 'nullable|string|max:3000',
            'acuerdos.*.responsable' => 'nullable|string|max:150',
            'acuerdos.*.fecha_entrega' => 'nullable|string|max:50',
        ]);

        $acta->fill($data);
        $acta->save();

        event(new \App\Events\DocumentUpdated($acta->curso_id));

        return back()->with('success', 'Acta actualizada.');
    }

    /** Delete an Acta. */
    public function destroy(Request $request, Acta $acta)
    {
        $this->authorize('update', $acta);
        $cursoId = $acta->curso_id;
        $acta->delete();
        event(new \App\Events\DocumentUpdated($cursoId));
        return back()->with('success', 'Acta eliminada.');
    }
}
