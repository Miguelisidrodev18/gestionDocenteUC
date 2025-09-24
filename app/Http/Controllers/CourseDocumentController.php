<?php

namespace App\Http\Controllers;

use App\Models\CourseDocument;
use App\Models\Curso;
use App\Notifications\DocumentSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CourseDocumentController extends Controller
{
    public function store(Request $request, Curso $curso)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,docm,dotx,dotm',
        ]);

        $docente = $request->user()->docente;

        if (! $docente) {
            abort(403, 'Debes tener un perfil de docente para cargar documentos.');
        }

        $uploadedFile = $request->file('document');
        $path = $uploadedFile->store('course-documents', 'public');

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
