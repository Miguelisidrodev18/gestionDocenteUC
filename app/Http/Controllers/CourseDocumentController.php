<?php

namespace App\Http\Controllers;

use App\Models\CourseDocument;
use App\Models\Curso;
use App\Notifications\DocumentSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseDocumentController extends Controller
{
    public function store(Request $request, Curso $curso)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,docm,dotx,dotm',
            'nombre' => 'nullable|string|max:150',
        ]);

        $user = $request->user();
        $docente = $user->docente;

        if (! $docente) {
            abort(403, 'Debes tener un perfil de docente para cargar documentos.');
        }

        if (! $curso->userCanUpload($user)) {
            abort(403, 'No puedes subir documentos para este curso.');
        }

        $uploadedFile = $request->file('document');
        $dir = 'course-documents';
        $ext = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension());
        $base = trim((string) $request->input('nombre'));
        $base = $base !== '' ? Str::slug(pathinfo($base, PATHINFO_FILENAME)) : Str::slug(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
        $candidate = $base !== '' ? $base : 'documento';
        $filename = $candidate . ($ext ? '.'.$ext : '');
        $i = 1;
        while (Storage::disk('public')->exists($dir.'/'.$filename)) {
            $filename = $candidate.'-'.$i.($ext ? '.'.$ext : '');
            $i++;
        }
        $path = $uploadedFile->storeAs($dir, $filename, 'public');

        $document = CourseDocument::create([
            'curso_id' => $curso->id,
            'docente_id' => $docente->id,
            'uploaded_by' => $request->user()->id,
            'path' => $path,
            'mime' => $uploadedFile->getClientMimeType(),
        ])->loadMissing(['curso', 'docente']);

        $responsable = $curso->user;

        if ($responsable) {
            Notification::send($responsable, new DocumentSubmitted($document));
        }

        return redirect()
            ->route('cursos.index')
            ->with('success', 'Documento cargado correctamente.');
    }
}
