<?php

namespace App\Domain\Actividades;

use RuntimeException;

class ReservaDuplicada extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ya tenés reservado este turno.');
    }
}
