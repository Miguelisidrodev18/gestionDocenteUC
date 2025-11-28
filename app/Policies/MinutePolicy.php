<?php

namespace App\Policies;

use App\Models\Acta;
use App\Models\Curso;
use App\Models\User;

class MinutePolicy
{
    public function view(User $user, Acta $acta): bool
    {
        return $acta->curso
            ? app(CoursePolicy::class)->view($user, $acta->curso)
            : false;
    }

    public function create(User $user, Curso $curso): bool
    {
        return app(CoursePolicy::class)->uploadEvidence($user, $curso);
    }

    public function update(User $user, Acta $acta): bool
    {
        return $acta->curso
            ? app(CoursePolicy::class)->uploadEvidence($user, $acta->curso)
            : false;
    }
}

