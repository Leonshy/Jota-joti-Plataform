<?php

namespace App\Domain\Actividades;

use Carbon\CarbonInterface;
use RuntimeException;

/** D3: se bloquea e informa quién tiene el choque. */
class ChoqueDeHorario extends RuntimeException
{
    /** @param  list<array{quien: string, actividad: string, inicio: CarbonInterface, fin: CarbonInterface}>  $choques */
    public function __construct(public readonly array $choques)
    {
        $primero = $choques[0];

        parent::__construct(sprintf(
            'Choca con "%s" de %s (%s a %s).',
            $primero['actividad'],
            $primero['quien'],
            $primero['inicio']->format('H:i'),
            $primero['fin']->format('H:i'),
        ));
    }
}
