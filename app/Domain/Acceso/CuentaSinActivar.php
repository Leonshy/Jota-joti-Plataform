<?php

namespace App\Domain\Acceso;

use RuntimeException;

class CuentaSinActivar extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Todavía no creaste tu PIN.');
    }
}
