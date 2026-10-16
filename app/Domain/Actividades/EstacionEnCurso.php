<?php

namespace App\Domain\Actividades;

use Carbon\CarbonInterface;
use RuntimeException;

/** Un equipo tiene una sola estación JOTA reservada a la vez: hasta que termine esa, no puede reservar otra. */
class EstacionEnCurso extends RuntimeException
{
    public function __construct(public readonly string $actividad, public readonly CarbonInterface $inicio, public readonly CarbonInterface $fin)
    {
        parent::__construct(sprintf('Ya tienen reservada la estación "%s" (%s a %s). Pueden reservar otra cuando termine.', $actividad, $inicio->format('H:i'), $fin->format('H:i')));
    }
}
