<?php

namespace App\Policies;

use App\Models\AdvisorAssignment;
use App\Models\User;

class AdvisorAssignmentPolicy
{
    public function updateStatus(User $user, AdvisorAssignment $assignment): bool
    {
        if ($user->isAdmin() || $user->isResponsable()) {
            return true;
        }

        $docenteId = $user->docente?->id;

        return $docenteId && $assignment->docente_id === $docenteId;
    }
}

