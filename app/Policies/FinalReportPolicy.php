<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\InformeFinal;
use App\Models\User;

class FinalReportPolicy
{
    public function view(User $user, InformeFinal $informe): bool
    {
        return $informe->curso
            ? app(CoursePolicy::class)->view($user, $informe->curso)
            : false;
    }

    public function create(User $user, Curso $curso): bool
    {
        return app(CoursePolicy::class)->uploadEvidence($user, $curso);
    }

    public function update(User $user, InformeFinal $informe): bool
    {
        return $informe->curso
            ? app(CoursePolicy::class)->uploadEvidence($user, $informe->curso)
            : false;
    }
}

