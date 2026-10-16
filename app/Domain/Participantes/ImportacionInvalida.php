<?php

namespace App\Domain\Participantes;

use RuntimeException;

/** El archivo no se puede ni leer (vacío, muy grande, sin las columnas necesarias). */
class ImportacionInvalida extends RuntimeException {}
