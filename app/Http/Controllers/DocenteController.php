<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocenteController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user && $user->role === 'docente') {
            $own = Docente::where('user_id', $user->id)->get();

            return Inertia::render('Docentes/index', [
                'docents' => $own,
            ]);
        }

        return Inertia::render('Docentes/index', [
            'docents' => Docente::all(),
        ]);
    }

    public function create()
    {
        if (auth()->user()?->role === 'docente') {
            abort(403);
        }

        return Inertia::render('Docentes/create');
    }

    public function store(Request $request)
    {
        if (auth()->user()?->role === 'docente') {
            abort(403);
        }

        $validated = $request->validate([
            'nombre'      => 'required|string|max:255',
            'apellido'    => 'required|string|max:255',
            'dni'         => 'required|string|max:20',
            'email'       => 'nullable|email|max:255',
            'telefono'    => 'nullable|string|max:20',
            'especialidad'=> 'nullable|string|max:255',
            'cv_personal' => 'nullable|file|mimes:pdf',
            'cv_sunedu'   => 'nullable|file|mimes:pdf',
            'cul'         => 'nullable|file|mimes:pdf',
            'linkedin'    => 'nullable|string|max:255',
            'estado'      => 'required|string|in:activo,inactivo',
            'cip'         => 'nullable|string|max:50',
        ]);

        // CV personal
        if ($request->hasFile('cv_personal')) {
            $file = $request->file('cv_personal');
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $base = trim((string) $request->input('cv_personal_nombre')) ?: 'cv-personal';
            $name = Str::slug(pathinfo($base, PATHINFO_FILENAME)).($ext ? '.'.$ext : '.pdf');
            $validated['cv_personal'] = $file->storeAs('cv', $name, 'public');
        }

        // CV Sunedu
        if ($request->hasFile('cv_sunedu')) {
            $file = $request->file('cv_sunedu');
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $base = trim((string) $request->input('cv_sunedu_nombre')) ?: 'cv-sunedu';
            $name = Str::slug(pathinfo($base, PATHINFO_FILENAME)).($ext ? '.'.$ext : '.pdf');
            $validated['cv_sunedu'] = $file->storeAs('cv', $name, 'public');
        }

        // Certificado Único Laboral (CUL)
        if ($request->hasFile('cul')) {
            $file = $request->file('cul');
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $base = trim((string) $request->input('cul_nombre')) ?: 'cul';
            $name = Str::slug(pathinfo($base, PATHINFO_FILENAME)).($ext ? '.'.$ext : '.pdf');
            $validated['cul'] = $file->storeAs('cul', $name, 'public');
        }

        Docente::create($validated);

        return redirect()->route('teachers.index')->with('success', 'Docente creado correctamente');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(Docente $docent)
    {
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

        $user = auth()->user();
        if ($user && $user->role === 'docente' && $docente->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'nombre'      => 'required|string|max:255',
            'apellido'    => 'required|string|max:255',
            'dni'         => 'required|string|max:20',
            'email'       => 'nullable|email|max:255',
            'telefono'    => 'nullable|string|max:20',
            'especialidad'=> 'nullable|string|max:255',
            'cv_personal' => 'nullable|file|mimes:pdf',
            'cv_sunedu'   => 'nullable|file|mimes:pdf',
            'cul'         => 'nullable|file|mimes:pdf',
            'linkedin'    => 'nullable|string|max:255',
            'estado'      => 'required|string|in:activo,inactivo',
            'cip'         => 'nullable|string|max:50',
            'cv_personal_delete' => 'nullable|boolean',
            'cv_sunedu_delete'   => 'nullable|boolean',
            'cul_delete'         => 'nullable|boolean',
        ]);

        // Conservar valores actuales
        $validated['cv_personal'] = $docente->cv_personal;
        $validated['cv_sunedu'] = $docente->cv_sunedu;
        $validated['cul'] = $docente->cul;

        // Eliminar archivos existentes si se solicita
        if ($request->boolean('cv_personal_delete') && ! $request->hasFile('cv_personal')) {
            if ($docente->cv_personal) {
                Storage::disk('public')->delete($docente->cv_personal);
            }
            $validated['cv_personal'] = null;
        }

        if ($request->boolean('cv_sunedu_delete') && ! $request->hasFile('cv_sunedu')) {
            if ($docente->cv_sunedu) {
                Storage::disk('public')->delete($docente->cv_sunedu);
            }
            $validated['cv_sunedu'] = null;
        }

        if ($request->boolean('cul_delete') && ! $request->hasFile('cul')) {
            if ($docente->cul) {
                Storage::disk('public')->delete($docente->cul);
            }
            $validated['cul'] = null;
        }

        // Subir nuevos archivos
        if ($request->hasFile('cv_personal')) {
            $file = $request->file('cv_personal');
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $base = trim((string) $request->input('cv_personal_nombre')) ?: 'cv-personal';
            $name = Str::slug(pathinfo($base, PATHINFO_FILENAME)).($ext ? '.'.$ext : '.pdf');
            $validated['cv_personal'] = $file->storeAs('cv', $name, 'public');
        }

        if ($request->hasFile('cv_sunedu')) {
            $file = $request->file('cv_sunedu');
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $base = trim((string) $request->input('cv_sunedu_nombre')) ?: 'cv-sunedu';
            $name = Str::slug(pathinfo($base, PATHINFO_FILENAME)).($ext ? '.'.$ext : '.pdf');
            $validated['cv_sunedu'] = $file->storeAs('cv', $name, 'public');
        }

        if ($request->hasFile('cul')) {
            $file = $request->file('cul');
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $base = trim((string) $request->input('cul_nombre')) ?: 'cul';
            $name = Str::slug(pathinfo($base, PATHINFO_FILENAME)).($ext ? '.'.$ext : '.pdf');
            $validated['cul'] = $file->storeAs('cul', $name, 'public');
        }

        $docente->update($validated);

        return redirect()->route('teachers.index')->with('success', 'Docente actualizado correctamente');
    }

    public function destroy($id)
    {
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
