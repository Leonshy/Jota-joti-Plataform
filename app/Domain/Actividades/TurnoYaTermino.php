<?php

namespace App\Domain\Actividades;

use RuntimeException;

class TurnoYaTermino extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este turno ya terminó, no se puede reservar.');
    }
}
