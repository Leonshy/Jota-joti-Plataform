<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $user, User $objetivo): bool
    {
        return $user->is($objetivo) || $user->puedeGestionarUnidad($objetivo->unidad_id);
    }

    /** Penalizar o consultar a una persona: dirigente de su unidad o admin. */
    public function gestionar(User $user, User $objetivo): bool
    {
        return $user->puedeGestionarUnidad($objetivo->unidad_id);
    }

    public function resetearPin(User $user, User $objetivo): bool
    {
        return $user->esAdmin();
    }

    /** Carga masiva de participantes y exportaciones: solo el administrador. */
    public function importar(User $user): bool
    {
        return $user->esAdmin();
    }

    public function exportar(User $user): bool
    {
        return $user->esAdmin();
    }
}
