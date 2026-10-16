<?php

namespace App\Domain\Actividades;

use RuntimeException;

class SesionCerrada extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('La sesión de asistencia ya se cerró.');
    }
}
