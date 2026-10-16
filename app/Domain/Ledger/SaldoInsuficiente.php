<?php

namespace App\Domain\Ledger;

use RuntimeException;

class SaldoInsuficiente extends RuntimeException
{
    public function __construct(public readonly int $saldo, public readonly int $monto)
    {
        parent::__construct("Saldo insuficiente: tiene {$saldo} y se piden {$monto}.");
    }
}
