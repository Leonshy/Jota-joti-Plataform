<?php

namespace App\Domain\Actividades;

use RuntimeException;

class TurnoLleno extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este turno ya no tiene lugares.');
    }
}
