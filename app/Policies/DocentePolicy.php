<?php

namespace App\Policies;

use App\Models\Docente;
use App\Models\User;

class DocentePolicy
{
    public function view(User $user, Docente $docente): bool
    {
        if ($user->isAdmin() || $user->isResponsable()) {
            return true;
        }

        return $docente->user_id === $user->id;
    }

    public function update(User $user, Docente $docente): bool
    {
        if ($user->isAdmin() || $user->isResponsable()) {
            return true;
        }

        return $docente->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsable();
    }
}

