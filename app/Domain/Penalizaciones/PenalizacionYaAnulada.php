<?php

namespace App\Domain\Penalizaciones;

use RuntimeException;

/** Respuesta de "ya registrado" (CLAUDE.md §2.3): una penalización se anula una sola vez. */
class PenalizacionYaAnulada extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta penalización ya fue anulada.');
    }
}
