<?php

namespace App\Domain\Participantes;

/** @phpstan-type Error array{linea: int, campo: string, mensaje: string} */
class ResultadoImportacion
{
    /**
     * @param  list<array{linea: int, campo: string, mensaje: string}>  $errores
     * @param  list<string>  $patrullasNuevas
     */
    public function __construct(
        public readonly int $filas,
        public readonly int $importados,
        public readonly array $errores,
        public readonly array $patrullasNuevas,
        public readonly bool $simulacro,
    ) {}

    public function valido(): bool
    {
        return $this->errores === [];
    }

    /** Líneas del archivo con al menos un error. */
    public function lineasConError(): int
    {
        return count(array_unique(array_column($this->errores, 'linea')));
    }
}
