<?php

namespace App\Domain\Misiones;

use App\Enums\EstadoEntrega;
use RuntimeException;

class EntregaBloqueada extends RuntimeException
{
    public function __construct(public readonly EstadoEntrega $estado)
    {
        parent::__construct($estado === EstadoEntrega::Aprobada
            ? 'Esta misión ya fue aprobada.'
            : 'Ya tenés una entrega esperando revisión.');
    }
}
