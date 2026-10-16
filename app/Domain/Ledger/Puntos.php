<?php

namespace App\Domain\Ledger;

use App\Models\MovPuntoIndividual;
use App\Models\MovPuntoPatrulla;
use App\Models\Patrulla;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Único camino para cambiar puntos (CLAUDE.md §2.2). Los puntos pueden quedar negativos (D14).
 */
class Puntos
{
    public function individual(
        User $user,
        int $cantidad,
        ?Model $origen = null,
        ?string $motivo = null,
        ?User $registradoPor = null,
    ): MovPuntoIndividual {
        $this->validar($cantidad);

        return DB::transaction(function () use ($user, $cantidad, $origen, $motivo, $registradoPor) {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $mov = MovPuntoIndividual::create([
                'user_id' => $user->getKey(),
                'cantidad' => $cantidad,
                'origen_type' => $origen?->getMorphClass(),
                'origen_id' => $origen?->getKey(),
                'motivo' => $motivo,
                'registrado_por' => $registradoPor?->getKey(),
            ]);

            User::query()->whereKey($user->getKey())->increment('puntos', $cantidad);

            return $mov;
        });
    }

    public function patrulla(
        Patrulla $patrulla,
        int $cantidad,
        ?Model $origen = null,
        ?string $motivo = null,
        ?User $registradoPor = null,
    ): MovPuntoPatrulla {
        $this->validar($cantidad);

        return DB::transaction(function () use ($patrulla, $cantidad, $origen, $motivo, $registradoPor) {
            Patrulla::query()->whereKey($patrulla->getKey())->lockForUpdate()->firstOrFail();

            $mov = MovPuntoPatrulla::create([
                'patrulla_id' => $patrulla->getKey(),
                'cantidad' => $cantidad,
                'origen_type' => $origen?->getMorphClass(),
                'origen_id' => $origen?->getKey(),
                'motivo' => $motivo,
                'registrado_por' => $registradoPor?->getKey(),
            ]);

            Patrulla::query()->whereKey($patrulla->getKey())->increment('puntos', $cantidad);

            return $mov;
        });
    }

    private function validar(int $cantidad): void
    {
        if ($cantidad === 0) {
            throw new InvalidArgumentException('Un movimiento de puntos no puede ser de cantidad cero.');
        }
    }
}
