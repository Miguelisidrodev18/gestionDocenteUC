<?php

namespace App\Policies;

use App\Models\ImprovementOpportunity;
use App\Models\User;

class ImprovementOpportunityPolicy
{
    public function view(User $user, ImprovementOpportunity $op): bool
    {
        if ($user->isAdmin() || $user->isResponsable()) {
            return true;
        }

        return $op->owner_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsable();
    }

    public function update(User $user, ImprovementOpportunity $op): bool
    {
        if ($user->isAdmin() || $user->isResponsable()) {
            return true;
        }

        return $op->owner_user_id === $user->id;
    }

    public function delete(User $user, ImprovementOpportunity $op): bool
    {
        return $this->update($user, $op);
    }
}

