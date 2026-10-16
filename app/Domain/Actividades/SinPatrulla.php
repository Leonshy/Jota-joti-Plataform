<?php

namespace App\Domain\Actividades;

use RuntimeException;

class SinPatrulla extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta actividad es por equipo y no pertenecés a ninguna patrulla.');
    }
}
