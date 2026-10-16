<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\User;

class ReservaPolicy
{
    /**
     * Cancela quien reservó, cualquier integrante si la reserva es de la patrulla, el dirigente de la
     * unidad de la persona o patrulla que reservó (CLAUDE.md §2.7), o el admin.
     */
    public function cancelar(User $user, Reserva $reserva): bool
    {
        return $user->esAdmin()
            || $reserva->user_id === $user->getKey()
            || ($reserva->patrulla_id !== null && $reserva->patrulla_id === $user->patrulla_id)
            || ($reserva->unidad_id !== null && $reserva->unidad_id === $user->unidad_id) // el equipo de los Rovers es toda la unidad
            || $user->puedeGestionarUnidad($this->unidadDe($reserva));
    }

    private function unidadDe(Reserva $reserva): ?int
    {
        return match (true) {
            $reserva->user_id !== null => $reserva->user()->value('unidad_id'),
            $reserva->patrulla_id !== null => $reserva->patrulla()->value('unidad_id'),
            default => $reserva->unidad_id,
        };
    }
}
