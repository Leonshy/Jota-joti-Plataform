<?php

namespace App\Domain\Acceso;

use RuntimeException;

class PinIncorrecto extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('PIN incorrecto.');
    }
}
