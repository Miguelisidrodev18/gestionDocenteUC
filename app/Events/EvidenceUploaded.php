<?php

namespace App\Events;

class EvidenceUploaded
{
    public function __construct(
        public int $cursoId,
    ) {
    }
}
