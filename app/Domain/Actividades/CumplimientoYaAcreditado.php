<?php

namespace App\Domain\Actividades;

use RuntimeException;

/** Una patrulla ya marcada como cumplida no se vuelve a acreditar ni se revierte desde acá. */
class CumplimientoYaAcreditado extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta patrulla ya figura como cumplida en esta actividad.');
    }
}
