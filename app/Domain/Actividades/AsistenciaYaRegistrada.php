<?php

namespace App\Domain\Actividades;

use RuntimeException;

/** Respuesta de "ya registrado" (CLAUDE.md §2.3), no un fallo. */
class AsistenciaYaRegistrada extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ya registraste tu asistencia en esta actividad.');
    }
}
