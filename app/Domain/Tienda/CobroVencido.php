<?php

namespace App\Domain\Tienda;

use RuntimeException;

class CobroVencido extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El cobro venció. Pedile a la tienda que genere uno nuevo.');
    }
}
