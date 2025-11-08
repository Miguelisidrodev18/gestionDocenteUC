<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\InformeFinal;
use Illuminate\Http\Request;

class InformeFinalController extends Controller
{
    public function store(Request $request, Curso $curso)
    {
        if (! $curso->userCanUpload($request->user())) {
            abort(403, 'No puedes registrar el informe final para este curso.');
        }

        $data = $request->validate([
            'responsable' => 'nullable|string|max:150',
            'fecha_presentacion' => 'nullable|date',
            'resultados' => 'nullable|array',
            'mejoras' => 'nullable|array',
        ]);

        $informe = InformeFinal::updateOrCreate(
            ['curso_id' => $curso->id],
            array_merge($data, [
                'created_by' => $request->user()->id,
            ])
        );

        return back()->with('success', 'Informe final guardado.');
    }

    /** Printable HTML preview (para imprimir/guardar como PDF desde el navegador). */
    public function preview(Curso $curso)
    {
        $informe = $curso->informeFinal()->first();
        if (! $informe) {
            abort(404, 'No hay informe final registrado.');
        }
        return view('pdf.informe_final', [
            'curso' => $curso,
            'informe' => $informe,
        ]);
    }
}
