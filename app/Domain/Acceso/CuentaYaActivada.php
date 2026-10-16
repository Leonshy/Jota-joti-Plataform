<?php

namespace App\Domain\Acceso;

use RuntimeException;

class CuentaYaActivada extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta cuenta ya tiene PIN. Ingresá con tu CI y tu PIN.');
    }
}
