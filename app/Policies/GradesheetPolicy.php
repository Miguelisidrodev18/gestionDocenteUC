<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\RegistroNota;
use App\Models\User;

class GradesheetPolicy
{
    public function view(User $user, RegistroNota $registro): bool
    {
        return $registro->curso
            ? app(CoursePolicy::class)->view($user, $registro->curso)
            : false;
    }

    public function create(User $user, Curso $curso): bool
    {
        return app(CoursePolicy::class)->uploadEvidence($user, $curso);
    }

    public function update(User $user, RegistroNota $registro): bool
    {
        return $registro->curso
            ? app(CoursePolicy::class)->uploadEvidence($user, $registro->curso)
            : false;
    }
}

