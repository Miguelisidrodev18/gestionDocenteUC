<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case RESPONSABLE = 'responsable';
    case DOCENTE = 'docente';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::RESPONSABLE => 'Responsable',
            self::DOCENTE => 'Docente',
        };
    }
}

