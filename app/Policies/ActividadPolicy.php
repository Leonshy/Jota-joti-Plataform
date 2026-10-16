<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Actividad;
use App\Models\User;

class ActividadPolicy
{
    public function ver(User $user, Actividad $actividad): bool
    {
        return $user->esAdmin() || $actividad->esParaUnidad($user->unidad_id);
    }

    /** Reservan y marcan asistencia los beneficiarios de las unidades destino. */
    public function reservar(User $user, Actividad $actividad): bool
    {
        return $user->rol === Rol::Beneficiario && $actividad->esParaUnidad($user->unidad_id);
    }

    public function asistir(User $user, Actividad $actividad): bool
    {
        return $this->reservar($user, $actividad);
    }

    /** Sesiones de asistencia y cumplimientos: dirigente de una unidad destino, o admin (CLAUDE.md §2.7). */
    public function gestionar(User $user, Actividad $actividad): bool
    {
        return $user->esAdmin()
            || ($user->rol === Rol::Dirigente && $actividad->esParaUnidad($user->unidad_id));
    }
}
