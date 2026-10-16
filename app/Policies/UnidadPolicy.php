<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Unidad;
use App\Models\User;

class UnidadPolicy
{
    /** Tabla anónima de la unidad: sus beneficiarios y su dirigente (D6: sin nombres). */
    public function verRanking(User $user, Unidad $unidad): bool
    {
        return in_array($user->rol, [Rol::Beneficiario, Rol::Dirigente], true)
            && $user->unidad_id === $unidad->getKey();
    }

    /** Ranking con nombres y desglose: solo el administrador (D6). */
    public function verRankingCompleto(User $user, Unidad $unidad): bool
    {
        return $user->esAdmin();
    }

    /** Validar entregas, abrir sesiones, marcar cumplimientos y penalizar dentro de la unidad. */
    public function gestionar(User $user, Unidad $unidad): bool
    {
        return $user->puedeGestionarUnidad($unidad->getKey());
    }
}
