<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** JOTA: contacto por radio (suma a la patrulla). JOTI: contacto por internet (suma a la persona). */
enum TipoContacto: string implements HasLabel
{
    case Jota = 'jota';
    case Joti = 'joti';

    public function getLabel(): string
    {
        return match ($this) {
            self::Jota => 'JOTA',
            self::Joti => 'JOTI',
        };
    }

    public function esJota(): bool
    {
        return $this === self::Jota;
    }
}
