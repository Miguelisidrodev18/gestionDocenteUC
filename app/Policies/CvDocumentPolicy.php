<?php

namespace App\Policies;

use App\Models\CvDocument;
use App\Models\User;

class CvDocumentPolicy
{
    /**
     * Determina si el usuario puede ver / descargar un CV.
     * - Propietario del registro (uploaded_by)
     * - Docente dueño del CV (relación docente)
     * - Usuarios con rol admin o responsable
     */
    public function view(User $user, CvDocument $cv): bool
    {
        if ($user->isAdmin() || $user->isResponsable()) {
            return true;
        }

        if ($cv->uploaded_by === $user->id) {
            return true;
        }

        if ($user->docente && $cv->docente_id === $user->docente->id) {
            return true;
        }

        return false;
    }
}

