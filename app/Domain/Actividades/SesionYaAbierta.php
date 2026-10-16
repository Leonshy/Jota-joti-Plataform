<?php

namespace App\Domain\Actividades;

use App\Models\SesionAsistencia;
use RuntimeException;

class SesionYaAbierta extends RuntimeException
{
    public function __construct(public readonly ?SesionAsistencia $sesion)
    {
        parent::__construct('Esta actividad ya tiene una sesión de asistencia abierta.');
    }
}
