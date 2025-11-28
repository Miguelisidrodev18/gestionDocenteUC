<?php

namespace App\Policies;

use App\Models\Panel;
use App\Models\User;

class PanelPolicy
{
    public function view(User $user, Panel $panel): bool
    {
        if ($user->isAdmin() || $user->isResponsable()) {
            return true;
        }

        $docenteId = $user->docente?->id;
        if (! $docenteId) {
            return false;
        }

        return $panel->assignments()
            ->where('docente_id', $docenteId)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsable();
    }

    public function update(User $user, Panel $panel): bool
    {
        return $user->isAdmin() || $user->isResponsable();
    }

    public function delete(User $user, Panel $panel): bool
    {
        return $user->isAdmin() || $user->isResponsable();
    }
}

