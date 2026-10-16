<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoActividad: string implements HasLabel
{
    case Taller = 'taller';
    case EstacionJota = 'estacion_jota';

    public function getLabel(): string
    {
        return match ($this) {
            self::Taller => 'Taller',
            self::EstacionJota => 'Estación JOTA',
        };
    }
}
