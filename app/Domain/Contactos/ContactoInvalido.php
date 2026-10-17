<?php

namespace App\Domain\Contactos;

use RuntimeException;

/** Datos del contacto que no sirven; el mensaje ya está en español y se le muestra a la persona. */
class ContactoInvalido extends RuntimeException {}
