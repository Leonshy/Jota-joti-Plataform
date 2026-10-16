<?php

namespace App\Domain\Acceso;

use App\Enums\Rol;
use App\Models\User;

/** A qué pantalla de inicio va cada rol después de ingresar. */
class DestinoPorRol
{
    public static function para(User $user): string
    {
        return match ($user->rol) {
            Rol::Beneficiario => route('app.inicio'),
            Rol::Dirigente => route('dirigente.inicio'),
            Rol::Tienda => route('tienda.inicio'),
            Rol::Admin => url('/admin'),
        };
    }
}
