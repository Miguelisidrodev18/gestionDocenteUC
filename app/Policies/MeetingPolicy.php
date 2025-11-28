<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    public function view(User $user, Meeting $meeting): bool
    {
        if (! $meeting->curso) {
            return false;
        }

        // Reutilizar las mismas reglas de acceso a cursos:
        // admin, responsable del curso y docentes asociados.
        return app(CoursePolicy::class)->view($user, $meeting->curso);
    }

    public function create(User $user, Curso $curso): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Solo responsable del curso
        return $user->isResponsable() && $curso->user_id === $user->id;
    }

    public function update(User $user, Meeting $meeting): bool
    {
        if (! $meeting->curso) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Solo responsable del curso
        return $user->isResponsable() && $meeting->curso->user_id === $user->id;
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $this->update($user, $meeting);
    }
}
