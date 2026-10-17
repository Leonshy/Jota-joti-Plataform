<?php

namespace App\Domain\Participantes;

use RuntimeException;

/** La cuenta no se puede eliminar sin romper algo que no es de ella; el mensaje explica qué y ya está en español. */
class EliminacionNoPermitida extends RuntimeException {}
