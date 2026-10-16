<?php

namespace App\Domain\Acceso;

use RuntimeException;

class CiNoRegistrado extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ese CI no está cargado en la plataforma.');
    }
}
