<?php

namespace App\Policies;

use App\Models\Update;
use App\Models\User;

class UpdatePolicy
{
    public function view(User $user, Update $update): bool
    {
        return Update::visibleFor($user)->where('id', $update->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isResponsable();
    }

    public function update(User $user, Update $update): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isResponsable() && $update->creado_por === $user->id;
    }

    public function delete(User $user, Update $update): bool
    {
        return $this->update($user, $update);
    }

    public function pin(User $user, Update $update): bool
    {
        return $this->update($user, $update);
    }

    public function unpin(User $user, Update $update): bool
    {
        return $this->pin($user, $update);
    }

    public function publish(User $user, Update $update): bool
    {
        return $this->update($user, $update);
    }
}

