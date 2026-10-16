<?php

namespace App\Domain\Actividades;

use App\Enums\ModoRanking;
use App\Models\Unidad;
use App\Models\User;

/**
 * El «equipo» con el que una persona hace las actividades por equipo: su patrulla (Tropa y Caminantes) o, en las unidades que
 * no se arman por patrulla (Rovers), toda la unidad. Devuelve la columna y el id con los que se guarda la reserva o el
 * cumplimiento, o null si todavía no tiene equipo (alguien de Tropa o Caminantes sin patrulla).
 */
class EquipoDeReserva
{
    /** @return array{patrulla_id: int}|array{unidad_id: int}|null */
    public static function de(User $user): ?array
    {
        if ($user->patrulla_id) {
            return ['patrulla_id' => $user->patrulla_id];
        }

        return self::unidadEsEquipo($user->unidad_id) ? ['unidad_id' => $user->unidad_id] : null;
    }

    /** ¿La unidad no se arma por patrulla y por lo tanto va toda junta como un solo equipo? */
    public static function unidadEsEquipo(?int $unidadId): bool
    {
        return $unidadId !== null
            && Unidad::query()->whereKey($unidadId)->where('modo_ranking', ModoRanking::Individual)->exists();
    }
}
