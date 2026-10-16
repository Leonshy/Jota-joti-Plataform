<?php

namespace App\Domain\Actividades;

use RuntimeException;

class TokenInvalido extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ese QR ya venció. Escaneá el que está en pantalla ahora.');
    }
}
