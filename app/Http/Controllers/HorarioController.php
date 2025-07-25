<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Horario;

class HorarioController extends Controller
{
    public function index()
    {
        $horarios = Horario::where('user_id', auth()->id())->get();
        return Inertia::render('Horarios/Index', [
            'horarios' => $horarios,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            // Agrega más campos según tu migración
        ]);

        Horarios::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'user_id' => auth()->id(),
            // Otros campos...
        ]);

        return redirect()->route('horarios.index')->with('success', 'Horario creado correctamente');
    }
}
