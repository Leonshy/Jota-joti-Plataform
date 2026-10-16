<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\User;

class CobroPolicy
{
    /** Genera cobros y hace devoluciones el usuario de tienda y el administrador. */
    public function crear(User $user): bool
    {
        return in_array($user->rol, [Rol::Tienda, Rol::Admin], true);
    }

    /** Pagan con sus LisCoins los beneficiarios (los dirigentes solo consultan, §4.8). */
    public function pagar(User $user): bool
    {
        return $user->rol === Rol::Beneficiario;
    }

    public function devolver(User $user): bool
    {
        return $this->crear($user);
    }

    public function buscar(User $user): bool
    {
        return $this->crear($user);
    }
}
