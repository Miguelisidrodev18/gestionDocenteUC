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
        $user = auth()->user();

        // If docente, only show their own Docente record
        if ($user && $user->role === 'docente') {
            $own = Docente::where('user_id', $user->id)->get();
            return Inertia::render('Docentes/index', [
                'docents' => $own,
            ]);
        }

        // Admin/responsable can see all
        return Inertia::render('Docentes/index', [
            'docents'=> Docente::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Only non-docente may access create
        if (auth()->user()?->role === 'docente') {
            abort(403);
        }
        return Inertia::render('Docentes/create',);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Only non-docente may create
        if (auth()->user()?->role === 'docente') {
            abort(403);
        }
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
            $file = $request->file('cv_personal');
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $base = trim((string) $request->input('cv_personal_nombre')) ?: 'cv-personal';
            $name = \Illuminate\Support\Str::slug(pathinfo($base, PATHINFO_FILENAME)).($ext?'.'.$ext:'pdf');
            $validated['cv_personal'] = $file->storeAs('cv', $name, 'public');
        }
        if ($request->hasFile('cv_sunedu')) {
            $file = $request->file('cv_sunedu');
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $base = trim((string) $request->input('cv_sunedu_nombre')) ?: 'cv-sunedu';
            $name = \Illuminate\Support\Str::slug(pathinfo($base, PATHINFO_FILENAME)).($ext?'.'.$ext:'pdf');
            $validated['cv_sunedu'] = $file->storeAs('cv', $name, 'public');
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

        // Docente users can only edit their own record
        $user = auth()->user();
        if ($user && $user->role === 'docente' && $docent->user_id !== $user->id) {
            abort(403);
        }

        return Inertia::render('Docentes/Edit', [
            'docent' => $docent,
        ]);
    }

    public function update(Request $request, $id)
    {
        $docente = Docente::findOrFail($id);

        // Docente users can only update their own record
        $user = auth()->user();
        if ($user && $user->role === 'docente' && $docente->user_id !== $user->id) {
            abort(403);
        }

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
        $file = $request->file('cv_personal');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $base = trim((string) $request->input('cv_personal_nombre')) ?: 'cv-personal';
        $name = \Illuminate\Support\Str::slug(pathinfo($base, PATHINFO_FILENAME)).($ext?'.'.$ext:'pdf');
        $validated['cv_personal'] = $file->storeAs('cv', $name, 'public');
    }

    if ($request->hasFile('cv_sunedu')) {
        $file = $request->file('cv_sunedu');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $base = trim((string) $request->input('cv_sunedu_nombre')) ?: 'cv-sunedu';
        $name = \Illuminate\Support\Str::slug(pathinfo($base, PATHINFO_FILENAME)).($ext?'.'.$ext:'pdf');
        $validated['cv_sunedu'] = $file->storeAs('cv', $name, 'public');
    }

    $docente->update($validated);

    // Redirigir con mensaje de éxito
    return redirect()->route('teachers.index')->with('success', 'Docente actualizado correctamente');
}

    public function destroy($id) 
    {       
        // Only non-docente may delete
        if (auth()->user()?->role === 'docente') {
            abort(403);
        }

        $docente = Docente::withCount('cursos')->findOrFail($id);

        if ($docente->cursos_count > 0) {
            return redirect()
                ->route('teachers.index')
                ->with('error', 'No se puede eliminar: el docente tiene cursos asociados.');
        }

        $docente->delete(); 
        return redirect()->route('teachers.index')->with('success', 'Docente eliminado correctamente.');
    } 
}
