<?php

namespace App\Http\Controllers;

use App\Models\CvDocument;
use App\Models\Docente;
use App\Services\CVBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CVController extends Controller
{
    /**
     * Descarga la plantilla base de CV para que el docente la pueda revisar o completar.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $path = 'plantillas/Plantilla_CV_Docente_Dinamica.docx';

        if (! Storage::disk('cvs')->exists($path)) {
            $source = base_path('resources/cv/Plantilla_CV_Docente_Dinamica.docx');

            if (! is_file($source)) {
                abort(404, 'Plantilla de CV no encontrada en resources/cv');
            }

            Storage::disk('cvs')->put($path, file_get_contents($source));
        }

        return Storage::disk('cvs')->download($path, 'Plantilla_CV_Docente.docx');
    }

    /**
     * Genera un CV DOCX completo a partir de la información del docente.
     */
    public function generateFilled(Request $request, Docente $docente, CVBuilder $builder): StreamedResponse
    {
        $filledPath = $builder->buildForDocente($docente);

        $filename = 'CV_'.$docente->apellido.'_'.$docente->nombre.'.docx';

        return Storage::disk('cvs')->download($filledPath, $filename);
    }

    /**
     * Permite subir un CV firmado / actualizado por el docente.
     */
    public function upload(Request $request, Docente $docente)
    {
        $request->validate([
            'cv' => 'required|file|mimes:doc,docx,pdf|max:8192',
        ]);

        $file = $request->file('cv');

        $path = $file->store("uploads/{$docente->id}", 'cvs');

        $cv = CvDocument::create([
            'docente_id'  => $docente->id,
            'path'        => $path,
            'mime'        => $file->getClientMimeType(),
            'size'        => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        // Opcional: reflejar el CV más reciente en el campo cv_personal
        // para que el módulo actual lo muestre con PdfFileCard
        $publicPath = $file->store("cv_docentes/{$docente->id}", 'public');
        $docente->cv_personal = $publicPath;
        $docente->save();

        // Auditoría básica: usuario e IP
        Log::info('CV subido', [
            'cv_id'      => $cv->id,
            'docente_id' => $docente->id,
            'user_id'    => $request->user()->id,
            'ip'         => $request->ip(),
        ]);

        return response()->json([
            'message' => 'CV subido correctamente',
            'id'      => $cv->id,
        ]);
    }

    /**
     * Descarga de una versión específica de CV historizada.
     */
    public function download(Docente $docente, CvDocument $cv): StreamedResponse
    {
        $this->authorize('view', $cv);

        return Storage::disk('cvs')->download($cv->path);
    }
}
