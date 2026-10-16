<?php

namespace App\Domain\Ledger;

use App\Models\Patrulla;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Regla de la tabla de acreditación (§4.5) para todo lo que se gana "por patrulla":
 * +P puntos al registro de la patrulla (una vez) y +⌈P/n⌉ LisCoins a cada integrante, con n el
 * número de integrantes al momento de acreditar. Los LisCoins son siempre de la persona.
 */
class AcreditarPatrulla
{
    public function __construct(private Puntos $puntos, private Jlis $jlis) {}

    public function __invoke(Patrulla $patrulla, int $puntos, Model $origen, string $motivo, ?User $registradoPor = null): void
    {
        if ($puntos > 0) {
            $this->puntos->patrulla($patrulla, $puntos, $origen, $motivo, $registradoPor);
        }

        // Orden por id: dos acreditaciones a la misma patrulla bloquean en el mismo orden (sin deadlock).
        $integrantes = User::query()->where('patrulla_id', $patrulla->getKey())->orderBy('id')->get();

        if ($puntos <= 0 || $integrantes->isEmpty()) {
            return;
        }

        $cuota = (int) ceil($puntos / $integrantes->count());

        foreach ($integrantes as $integrante) {
            $this->jlis->acreditar($integrante, $cuota, $origen, $motivo, $registradoPor);
        }
    }
}
