<?php

namespace App\Domain\Tienda;

use RuntimeException;

/** Respuesta de "ya registrado" (CLAUDE.md §2.3): el cobro tiene un solo pago. */
class CobroYaPagado extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este cobro ya fue pagado.');
    }
}
