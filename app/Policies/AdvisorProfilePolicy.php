<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\AdvisorProfile;
use App\Models\User;

class AdvisorProfilePolicy
{
    public function view(User $user, AdvisorProfile $profile): bool
    {
        if ($user->isAdmin() || $user->isResponsable()) {
            return true;
        }

        return $profile->docente?->user_id === $user->id;
    }

    public function update(User $user, AdvisorProfile $profile): bool
    {
        if ($user->isAdmin() || $user->isResponsable()) {
            return true;
        }

        return $profile->docente?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsable();
    }
}

