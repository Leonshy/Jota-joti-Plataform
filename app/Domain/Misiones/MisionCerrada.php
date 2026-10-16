<?php

namespace App\Domain\Misiones;

use RuntimeException;

class MisionCerrada extends RuntimeException
{
    public function __construct(public readonly string $razon)
    {
        parent::__construct($razon === 'vencida' ? 'La misión ya venció.' : 'La misión está cerrada.');
    }
}
