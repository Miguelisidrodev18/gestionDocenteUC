<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\Evidencia;
use App\Models\User;

class EvidencePolicy
{
    public function view(User $user, Evidencia $evidencia): bool
    {
        return $evidencia->curso
            ? app(CoursePolicy::class)->view($user, $evidencia->curso)
            : false;
    }

    public function create(User $user, Curso $curso): bool
    {
        return app(CoursePolicy::class)->uploadEvidence($user, $curso);
    }

    public function delete(User $user, Evidencia $evidencia): bool
    {
        return $evidencia->curso
            ? app(CoursePolicy::class)->uploadEvidence($user, $evidencia->curso)
            : false;
    }
}

