<?php

namespace App\Services;

use App\Models\Docente;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class CVBuilder
{
    /**
     * Genera un CV en DOCX para un docente dado y devuelve
     * la ruta relativa dentro del disco "cvs".
     */
    public function buildForDocente(Docente $docente): string
    {
        $templatePath = Storage::disk('cvs')->path('plantillas/Plantilla_CV_Docente_Dinamica.docx');

        $template = new TemplateProcessor($templatePath);
        $variables = $template->getVariables();

        // Mapeo de campos planos (adaptado a los campos reales del modelo Docente)
        $data = [
            'perfil_resumen' => (string) ($docente->perfil_resumen ?? ''),
            'nombres'        => (string) ($docente->nombre ?? ''),
            'apellidos'      => (string) ($docente->apellido ?? ''),
            'dni'            => (string) ($docente->dni ?? ''),
            'correo'         => (string) ($docente->email ?? ''),
            'telefono'       => (string) ($docente->telefono ?? ''),
            'registro_prof'  => (string) ($docente->cip ?? ''),
        ];

        foreach ($data as $key => $value) {
            $template->setValue($key, $value ?? '');
        }

        // Secciones repetibles (educación, experiencia, etc.).
        // Cada sección se ejecuta solo si la plantilla contiene la variable base.

        // Educación
        $educaciones = $this->safeRelation($docente, 'educaciones', 'fecha', [
            'grado',
            'disciplina',
            'institucion',
            'fecha',
        ]);

        if (in_array('edu_grado', $variables, true)) {
            $template->cloneRow('edu_grado', max($educaciones->count(), 1));

            foreach ($educaciones as $index => $educacion) {
                $row = $index + 1;

                $template->setValue("edu_grado#{$row}", $educacion->grado ?? '');
                $template->setValue("edu_disciplina#{$row}", $educacion->disciplina ?? '');
                $template->setValue("edu_institucion#{$row}", $educacion->institucion ?? '');

                $fecha = $educacion->fecha ?? null;
                $template->setValue("edu_fecha#{$row}", $fecha ? $fecha->format('Y-m') : '');
            }
        }

        // Experiencia académica
        $expAcad = $this->safeRelation($docente, 'experienciasAcademicas', 'inicio', [
            'institucion',
            'cargo',
            'categoria',
            'periodo',
            'dedicacion',
        ]);

        if (in_array('expA_institucion', $variables, true)) {
            $template->cloneRow('expA_institucion', max($expAcad->count(), 1));

            foreach ($expAcad as $index => $exp) {
                $row = $index + 1;

                $template->setValue("expA_institucion#{$row}", $exp->institucion ?? '');
                $template->setValue("expA_cargo#{$row}", $exp->cargo ?? '');
                $template->setValue("expA_categoria#{$row}", $exp->categoria ?? '');
                $template->setValue("expA_periodo#{$row}", $exp->periodo ?? '');
                $template->setValue("expA_dedicacion#{$row}", $exp->dedicacion ?? '');
            }
        }

        $outDir = "generados/{$docente->id}";

        if (! Storage::disk('cvs')->exists($outDir)) {
            Storage::disk('cvs')->makeDirectory($outDir);
        }

        $filename = $outDir.'/CV_'.$docente->id.'_'.now()->format('Ymd_His').'.docx';

        $template->saveAs(Storage::disk('cvs')->path($filename));

        return $filename;
    }

    /**
     * Obtiene de forma segura una relación del docente.
     * Si la relación o la tabla no existen, devuelve una colección vacía.
     *
     * @param  array<int,string>|null  $columns
     */
    private function safeRelation(Docente $docente, string $relation, ?string $orderBy = null, ?array $columns = null): Collection
    {
        if (! method_exists($docente, $relation)) {
            return collect();
        }

        try {
            $query = $docente->{$relation}();

            if ($orderBy !== null) {
                $query = $query->latest($orderBy);
            }

            if ($columns !== null) {
                $query = $query->get($columns);
            } else {
                $query = $query->get();
            }

            return $query;
        } catch (\Throwable $e) {
            // Si la tabla aún no existe u otro error, retorna vacío para no romper el flujo.
            return collect();
        }
    }
}

