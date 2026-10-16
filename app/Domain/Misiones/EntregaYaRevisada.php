<?php

namespace App\Domain\Misiones;

use App\Enums\EstadoEntrega;
use RuntimeException;

/** Respuesta de "ya registrado" (CLAUDE.md §2.3): no es un fallo, la entrega ya tiene resolución. */
class EntregaYaRevisada extends RuntimeException
{
    public function __construct(public readonly EstadoEntrega $estado)
    {
        parent::__construct('Esta entrega ya fue revisada.');
    }
}
