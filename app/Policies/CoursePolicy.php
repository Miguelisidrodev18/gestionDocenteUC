<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Curso;
use App\Models\User;

class CoursePolicy
{
    public function view(User $user, Curso $curso): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isResponsable() && $curso->user_id === $user->id) {
            return true;
        }

        $docenteId = $user->docente?->id;
        if (! $docenteId) {
            return false;
        }

        if ($curso->docente_id === $docenteId) {
            return true;
        }

        return $curso->docentesParticipantes()->where('docente_id', $docenteId)->exists();
    }

    public function update(User $user, Curso $curso): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isResponsable() && $curso->user_id === $user->id) {
            return true;
        }

        return $user->isDocente() && (int) $curso->user_id === (int) $user->id;
    }

    public function uploadEvidence(User $user, Curso $curso): bool
    {
        return $curso->userCanUpload($user);
    }

    public function assignResponsible(User $user, Curso $curso): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isResponsable() && $curso->user_id === $user->id;
    }

    /**
     * changeState cubre cambios administrativos como checklist, activar/desactivar, etc.
     */
    public function changeState(User $user, Curso $curso): bool
    {
        if ($user->isAdmin() || $user->isResponsable()) {
            return true;
        }

        return $user->isDocente() && (int) $curso->user_id === (int) $user->id;
    }
}
