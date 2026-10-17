<?php

namespace App\Domain\Contactos;

use App\Enums\EstadoEntrega;
use RuntimeException;

class ContactoYaRevisado extends RuntimeException
{
    public function __construct(public readonly EstadoEntrega $estado)
    {
        parent::__construct('Este contacto ya fue revisado.');
    }
}
