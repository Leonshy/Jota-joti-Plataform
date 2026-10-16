<?php

namespace App\Domain\Actividades;

use RuntimeException;

class TurnoYaComenzo extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este turno ya empezó, no se puede reservar ni cancelar.');
    }
}
