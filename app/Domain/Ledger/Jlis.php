<?php

namespace App\Domain\Ledger;

use App\Models\MovJli;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Único camino para cambiar LisCoins (CLAUDE.md §2.2). El saldo nunca queda negativo (§2.6):
 * un débito sin saldo suficiente lanza SaldoInsuficiente y no escribe nada.
 */
class Jlis
{
    public function acreditar(
        User $user,
        int $cantidad,
        ?Model $origen = null,
        ?string $motivo = null,
        ?User $registradoPor = null,
    ): MovJli {
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('Acreditar LisCoins requiere una cantidad positiva.');
        }

        return $this->mover($user, $cantidad, $origen, $motivo, $registradoPor);
    }

    /** @throws SaldoInsuficiente */
    public function debitar(
        User $user,
        int $monto,
        ?Model $origen = null,
        ?string $motivo = null,
        ?User $registradoPor = null,
    ): MovJli {
        if ($monto <= 0) {
            throw new InvalidArgumentException('Debitar LisCoins requiere un monto positivo.');
        }

        return $this->mover($user, -$monto, $origen, $motivo, $registradoPor);
    }

    private function mover(User $user, int $cantidad, ?Model $origen, ?string $motivo, ?User $registradoPor): MovJli
    {
        return DB::transaction(function () use ($user, $cantidad, $origen, $motivo, $registradoPor) {
            $bloqueado = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if ($bloqueado->jlis + $cantidad < 0) {
                throw new SaldoInsuficiente($bloqueado->jlis, -$cantidad);
            }

            $mov = MovJli::create([
                'user_id' => $user->getKey(),
                'cantidad' => $cantidad,
                'origen_type' => $origen?->getMorphClass(),
                'origen_id' => $origen?->getKey(),
                'motivo' => $motivo,
                'registrado_por' => $registradoPor?->getKey(),
            ]);

            User::query()->whereKey($user->getKey())->increment('jlis', $cantidad);

            return $mov;
        });
    }
}
