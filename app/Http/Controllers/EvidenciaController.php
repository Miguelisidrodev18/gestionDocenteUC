<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\Evidencia;
use App\Events\EvidenceUploaded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EvidenciaController extends Controller
{
    /**
     * Store an evidencia file for a course, optionally tied to a week.
     */
    public function store(Request $request, Curso $curso)
    {
        $request->validate([
            'tipo' => 'required|in:acta,guia,presentacion,trabajo,excel',
            'archivo' => 'required|file',
            'semana' => 'nullable|integer|min:1',
            'nombre' => 'nullable|string|max:150',
            'observaciones' => 'nullable|string',
            'nivel' => 'nullable|in:alto,medio,bajo',
        ]);

        $this->authorize('create', [Evidencia::class, $curso]);

        // Validate week range against modality duration if provided
        $weeks = $curso->modalidadRel?->duracion_semanas;
        if (! $weeks) {
            $mod = strtolower((string) $curso->modalidad);
            $weeks = str_contains($mod, 'semi') ? 8 : 16;
        }
        if ($request->filled('semana') && ((int) $request->integer('semana') > (int) $weeks)) {
            return back()->withErrors(['semana' => 'La semana excede la duración del curso ('.$weeks.').']);
        }

        // Enforce max requirements per type
        $req = $curso->requerimientos();
        $tipo = (string) $request->string('tipo');
        $maxCount = $req[$tipo]['max'] ?? null;
        if ($maxCount !== null) {
            $current = (int) $curso->evidencias()->where('tipo', $tipo)->count();
            if ($current >= (int) $maxCount) {
                return back()->withErrors(['archivo' => 'Límite alcanzado para '.$tipo.' (máx '.$maxCount.').']);
            }
        }

        $file = $request->file('archivo');
        $dir = 'evidencias/'.$curso->id.'/'.$request->string('tipo');
        if ($request->filled('semana')) {
            $dir .= '/semana-'.(int) $request->integer('semana');
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $base = trim((string) $request->input('nombre'));
        $base = $base !== '' ? Str::slug(pathinfo($base, PATHINFO_FILENAME)) : Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $candidate = $base !== '' ? $base : 'archivo';
        $filename = $candidate . ($ext ? '.'.$ext : '');
        $i = 1;
        while (Storage::disk('public')->exists($dir.'/'.$filename)) {
            $filename = $candidate.'-'.$i.($ext ? '.'.$ext : '');
            $i++;
        }
        $path = $file->storeAs($dir, $filename, 'public');

        Evidencia::create([
            'curso_id' => $curso->id,
            'user_id' => $request->user()->id,
            'tipo' => $request->string('tipo'),
            'archivo_path' => $path,
            'semana' => $request->input('semana'),
            'fecha_subida' => now(),
            'estado' => 'pendiente',
            'observaciones' => $request->input('observaciones'),
            'nivel' => $request->input('nivel'),
        ]);

        event(new EvidenceUploaded($curso->id));

        return back()->with('success', 'Evidencia subida correctamente.');
    }

    /** Quick progress endpoint for a course. */
    public function progress(Curso $curso)
    {
        return response()->json([
            'curso_id' => $curso->id,
            'avance' => $curso->avance,
            'requerimientos' => $curso->requerimientos(),
        ]);
    }

    /**
     * Delete an evidencia (and its file) if the user can manage the course.
     */
    public function destroy(Request $request, Evidencia $evidencia)
    {
        $this->authorize('delete', $evidencia);

        if ($evidencia->archivo_path && Storage::disk('public')->exists($evidencia->archivo_path)) {
            Storage::disk('public')->delete($evidencia->archivo_path);
        }
        $evidencia->delete();
        event(new EvidenceUploaded($curso->id));
        return back()->with('success', 'Evidencia eliminada.');
    }

    /**
     * Rename evidencia file (same directory) and optionally update semana/observaciones.
     */
    public function update(Request $request, Evidencia $evidencia)
    {
        $this->authorize('delete', $evidencia);

        $data = $request->validate([
            'nombre' => 'nullable|string',
            'semana' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string',
        ]);

        // Update semana bounds
        if (isset($data['semana'])) {
            $weeks = $curso->modalidadRel?->duracion_semanas;
            if (! $weeks) {
                $mod = strtolower((string) $curso->modalidad);
                $weeks = str_contains($mod, 'semi') ? 8 : 16;
            }
            if ((int) $data['semana'] > (int) $weeks) {
                return back()->withErrors(['semana' => 'La semana excede la duración del curso ('.$weeks.').']);
            }
        }

        // Handle rename
        if (! empty($data['nombre'])) {
            $oldPath = $evidencia->archivo_path;
            $dir = dirname($oldPath);
            $ext = pathinfo($oldPath, PATHINFO_EXTENSION);
            $basename = trim((string) $data['nombre']);
            // Ensure extension present
            if ($ext && ! str_ends_with(strtolower($basename), '.'.strtolower($ext))) {
                $basename .= '.'.$ext;
            }
            $newPath = $dir.'/'.$basename;
            if ($oldPath !== $newPath) {
                if (Storage::disk('public')->exists($newPath)) {
                    return back()->withErrors(['nombre' => 'Ya existe un archivo con ese nombre.']);
                }
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->move($oldPath, $newPath);
                }
                $evidencia->archivo_path = $newPath;
            }
        }

        if (array_key_exists('semana', $data)) {
            $evidencia->semana = $data['semana'];
        }
        if (array_key_exists('observaciones', $data)) {
            $evidencia->observaciones = $data['observaciones'];
        }

        $evidencia->save();
        event(new EvidenceUploaded($curso->curso_id));
        return back()->with('success', 'Evidencia actualizada.');
    }
}
