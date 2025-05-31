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
            'cv_personal' => 'nullable|file|mimes:pdf',
            'cv_sunedu' => 'nullable|file|mimes:pdf',
            'linkedin' => 'nullable|string|max:255',
            'estado' => 'required|string|in:activo,inactivo',
            'cip' => 'nullable|string|max:50',
        ]);

        // Guardar archivos si existen
        if ($request->hasFile('cv_personal')) {
            $validated['cv_personal'] = $request->file('cv_personal')->store('cv', 'public');
        }
        if ($request->hasFile('cv_sunedu')) {
            $validated['cv_sunedu'] = $request->file('cv_sunedu')->store('cv', 'public');
        }

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
    public function edit(Docente $docent)
    {
        // Log::info('Docente ID: ' . $docent->id); // Uncomment if you want to log the docente ID

        return Inertia::render('Docentes/Edit', [
            'docent' => $docent,
        ]);
    }

    public function update(Request $request, $id)
    {
        $docente = Docente::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'dni' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'especialidad' => 'nullable|string|max:255',
            'cv_personal' => 'nullable|file|mimes:pdf',
            'cv_sunedu' => 'nullable|file|mimes:pdf',
            'linkedin' => 'nullable|string|max:255',
            'estado' => 'required|string|in:activo,inactivo',
            'cip' => 'nullable|string|max:50',
        ]);

        // Manejar archivos
    if ($request->hasFile('cv_personal')) {
        $validated['cv_personal'] = $request->file('cv_personal')->store('cv', 'public');
    }

    if ($request->hasFile('cv_sunedu')) {
        $validated['cv_sunedu'] = $request->file('cv_sunedu')->store('cv', 'public');
    }

    $docente->update($validated);

    // Redirigir con mensaje de éxito
    return redirect()->route('teachers.index')->with('success', 'Docente actualizado correctamente');
}

    public function destroy($id) 
    {       
        $docente = Docente::findOrFail($id); 
        $docente->delete(); 
        return redirect()->route('teachers.index')->with('success', 'Docente eliminado correctamente.');
    } 
}