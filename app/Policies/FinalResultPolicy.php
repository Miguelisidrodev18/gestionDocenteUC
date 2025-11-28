<?php

namespace App\Policies;

use App\Models\FinalResult;
use App\Models\User;

class FinalResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsable();
    }

    public function view(User $user, FinalResult $result): bool
    {
        return $this->viewAny($user);
    }
}
