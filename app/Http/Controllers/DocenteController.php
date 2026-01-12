<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Docente;
use App\Models\Especialidad;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocenteController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isDocente()) {
            $docentes = Docente::where('user_id', $user->id)->get();
        } else {
            $docentes = Docente::all();
        }

        return Inertia::render('Docentes/index', [
            'docents' => $docentes,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Docente::class);

        return Inertia::render('Docentes/create', [
            'especialidades' => Especialidad::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Docente::class);

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
            'crear_usuario' => 'nullable|boolean',
            'user_password' => 'nullable|required_if:crear_usuario,1|string|min:8|confirmed',
            'user_password_confirmation' => 'nullable|string|min:8',
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

        // Crear usuario para el docente si se solicita y hay email
        if ($request->boolean('crear_usuario') && ! empty($validated['email'])) {
            $existingUser = User::where('email', $validated['email'])->first();

            if (! $existingUser) {
                $user = User::create([
                    'name' => trim($validated['nombre'].' '.$validated['apellido']),
                    'email' => $validated['email'],
                    'password' => Hash::make($request->input('user_password')),
                    'role' => Role::DOCENTE->value,
                ]);
            } else {
                $user = $existingUser;
            }

            $validated['user_id'] = $user->id;
        }

        $especialidadNombre = trim((string) ($validated['especialidad'] ?? ''));
        if ($especialidadNombre !== '') {
            $especialidad = Especialidad::firstOrCreate(['nombre' => $especialidadNombre]);
            $validated['especialidad'] = $especialidad->nombre;
        } else {
            $validated['especialidad'] = null;
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
        $this->authorize('update', $docent);

        return Inertia::render('Docentes/Edit', [
            'docent' => $docent,
            'especialidades' => Especialidad::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, $id)
    {
        $docente = Docente::findOrFail($id);

        $this->authorize('update', $docente);

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

        $especialidadNombre = trim((string) ($validated['especialidad'] ?? ''));
        if ($especialidadNombre !== '') {
            $especialidad = Especialidad::firstOrCreate(['nombre' => $especialidadNombre]);
            $validated['especialidad'] = $especialidad->nombre;
        } else {
            $validated['especialidad'] = null;
        }

        $docente->update($validated);

        return redirect()->route('teachers.index')->with('success', 'Docente actualizado correctamente');
    }

    public function destroy($id)
    {
        $docente = Docente::withCount('cursos')->findOrFail($id);
        $this->authorize('update', $docente);

        if ($docente->cursos_count > 0) {
            return redirect()
                ->route('teachers.index')
                ->with('error', 'No se puede eliminar: el docente tiene cursos asociados.');
        }

        $docente->delete();

        return redirect()->route('teachers.index')->with('success', 'Docente eliminado correctamente.');
    }
}
