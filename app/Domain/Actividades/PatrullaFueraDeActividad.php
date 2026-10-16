<?php

namespace App\Domain\Actividades;

use RuntimeException;

class PatrullaFueraDeActividad extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta actividad no es para la unidad de esa patrulla.');
    }
}
