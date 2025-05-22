<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocenteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Docentes/index', [
            'docents'=> Docente::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Docentes/create',);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'dni' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'especialidad' => 'nullable|string|max:255',
            'cv_personal' => 'nullable|string|max:255',
            'cv_sunedu' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:50',
            'cip' => 'nullable|string|max:50',
        ]);

        \App\Models\Docente::create($validated);

        return redirect()->route('teachers.index')->with('success', 'Docente creado correctamente');
    }

    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

   
    public function update(Request $request, string $id)
    {
        //
    }
    public function destroy($id) 
    {       
        $docente = Docente::findOrFail($id); 
        $docente->delete(); 
        return redirect()->route('teachers.index')->with('success', 'Docente eliminado correctamente.');
    } 
}