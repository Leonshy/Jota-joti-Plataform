<?php

namespace App\Domain\Actividades;

use RuntimeException;

/** Un taller se cursa una sola vez: quien ya está apuntado en un turno no puede apuntarse a otro turno del mismo taller. */
class TallerYaApuntado extends RuntimeException
{
    public function __construct(public readonly string $actividad, public readonly bool $porEquipo)
    {
        parent::__construct($porEquipo ? 'Tu equipo ya está apuntado a este taller.' : 'Ya estás apuntado a este taller.');
    }
}
