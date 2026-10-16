<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Mision;
use App\Models\User;

class MisionPolicy
{
    /** El admin ve todo; el resto, solo las misiones de su unidad ya lanzadas. */
    public function ver(User $user, Mision $mision): bool
    {
        return $user->esAdmin() || ($mision->estaLanzada() && $this->esDeSuUnidad($user, $mision));
    }

    /** Entregan los beneficiarios; los dirigentes solo consultan (§4.8). */
    public function entregar(User $user, Mision $mision): bool
    {
        return $user->rol === Rol::Beneficiario
            && $mision->estaLanzada()
            && $this->esDeSuUnidad($user, $mision);
    }

    public function cerrar(User $user, Mision $mision): bool
    {
        return $user->esAdmin();
    }

    private function esDeSuUnidad(User $user, Mision $mision): bool
    {
        return $user->unidad_id !== null
            && $mision->unidades()->whereKey($user->unidad_id)->exists();
    }
}
