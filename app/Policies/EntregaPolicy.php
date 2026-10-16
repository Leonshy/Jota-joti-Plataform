<?php

namespace App\Policies;

use App\Models\Entrega;
use App\Models\User;

class EntregaPolicy
{
    /** Quien entregó, su patrulla (si la misión es por patrulla), el dirigente de la unidad y el admin. */
    public function ver(User $user, Entrega $entrega): bool
    {
        return $user->is($entrega->user)
            || ($entrega->patrulla_id !== null && $user->patrulla_id === $entrega->patrulla_id)
            || $this->revisar($user, $entrega);
    }

    /** Aprobar o rechazar: dirigente de la unidad de quien entregó, o admin (CLAUDE.md §2.7). */
    public function revisar(User $user, Entrega $entrega): bool
    {
        return $user->puedeGestionarUnidad($entrega->user->unidad_id);
    }
}
