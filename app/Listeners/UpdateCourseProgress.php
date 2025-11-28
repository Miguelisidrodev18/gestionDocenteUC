<?php

namespace App\Listeners;

use App\Services\ProgressService;
use Illuminate\Support\Facades\Cache;

class UpdateCourseProgress
{
    public function __construct(
        protected ProgressService $progressService,
    ) {
    }

    public function handle(object $event): void
    {
        $cursoId = $event->cursoId ?? null;
        if (! $cursoId) {
            return;
        }

        $this->progressService->recomputeForCourse((int) $cursoId);

        // Invalidar métricas del dashboard (se regeneran en la próxima consulta)
        Cache::flush();
    }
}

