<?php

namespace App\Events;

class DocumentUpdated
{
    public function __construct(
        public int $cursoId,
    ) {
    }
}

