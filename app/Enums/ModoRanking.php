<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModoRanking: string implements HasLabel
{
    case Equipo = 'equipo';
    case Individual = 'individual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Equipo => 'Por equipo (patrullas)',
            self::Individual => 'Individual',
        };
    }
}
