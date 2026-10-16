<?php

namespace App\Domain\Tienda;

use RuntimeException;

class DevolucionExcedeCompras extends RuntimeException
{
    public function __construct(public readonly int $maximo)
    {
        parent::__construct($maximo > 0
            ? "Solo se pueden devolver hasta {$maximo} ".trans_choice('jota.unidad.jli', $maximo).': es lo que gastó en la tienda y no se le devolvió.'
            : 'Esta persona no gastó LisCoins en la tienda, no hay nada para devolver.');
    }
}
