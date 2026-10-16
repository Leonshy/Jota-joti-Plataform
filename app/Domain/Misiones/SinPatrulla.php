<?php

namespace App\Domain\Misiones;

use RuntimeException;

class SinPatrulla extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta misión es por patrulla y no pertenecés a ninguna.');
    }
}
