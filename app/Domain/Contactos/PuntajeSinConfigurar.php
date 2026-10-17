<?php

namespace App\Domain\Contactos;

use RuntimeException;

/** No se puede aprobar un contacto si el admin todavía no definió cuántos puntos vale. */
class PuntajeSinConfigurar extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Todavía no se definió el puntaje de este tipo de contacto.');
    }
}
