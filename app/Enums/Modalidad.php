<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Modalidad: string implements HasLabel
{
    case Individual = 'individual';
    case Patrulla = 'patrulla';

    public function getLabel(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::Patrulla => 'Por patrulla / equipo',
        };
    }
}
