<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Rol: string implements HasLabel
{
    case Beneficiario = 'beneficiario';
    case Dirigente = 'dirigente';
    case Tienda = 'tienda';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Beneficiario => 'Participante',
            self::Dirigente => 'Dirigente',
            self::Tienda => 'Tienda',
            self::Admin => 'Administrador',
        };
    }
}
