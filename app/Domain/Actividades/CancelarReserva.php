<?php

namespace App\Domain\Actividades;

use App\Enums\Rol;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CancelarReserva
{
    /**
     * @throws AuthorizationException
     * @throws TurnoYaComenzo
     */
    public function __invoke(User $user, Reserva $reserva): void
    {
        Gate::forUser($user)->authorize('cancelar', $reserva);

        DB::transaction(function () use ($user, $reserva) {
            $actual = Reserva::query()->with('turno')->whereKey($reserva->getKey())->lockForUpdate()->first();

            if (! $actual) {
                return; // ya estaba cancelada
            }

            // Quien reservó no puede cancelar un turno que ya empezó; el admin y el dirigente sí (p. ej. si no se presentó).
            if ($user->rol === Rol::Beneficiario && $actual->turno->haComenzado()) {
                throw new TurnoYaComenzo;
            }

            $actual->delete();
        });
    }
}
