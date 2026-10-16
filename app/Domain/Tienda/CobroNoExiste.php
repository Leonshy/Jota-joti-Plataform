<?php

namespace App\Domain\Tienda;

use RuntimeException;

class CobroNoExiste extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ese cobro no existe. Pedile a la tienda que genere uno nuevo.');
    }
}
