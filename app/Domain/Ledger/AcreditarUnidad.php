<?php

namespace App\Domain\Ledger;

use App\Enums\Rol;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Lo que se gana «por equipo» en una unidad que no se arma por patrulla (Rovers): el equipo es toda la unidad y, como su
 * ranking es individual, CADA integrante recibe los puntos y los LisCoins completos (+P y +P), no un reparto.
 */
class AcreditarUnidad
{
    public function __construct(private Puntos $puntos, private Jlis $jlis) {}

    public function __invoke(Unidad $unidad, int $puntos, Model $origen, string $motivo, ?User $registradoPor = null): int
    {
        if ($puntos <= 0) {
            return 0;
        }

        // Orden por id: dos acreditaciones a la misma unidad bloquean en el mismo orden (sin deadlock).
        $integrantes = User::query()->where('unidad_id', $unidad->getKey())->where('rol', Rol::Beneficiario)->orderBy('id')->get();

        foreach ($integrantes as $integrante) {
            $this->puntos->individual($integrante, $puntos, $origen, $motivo, $registradoPor);
            $this->jlis->acreditar($integrante, $puntos, $origen, $motivo, $registradoPor);
        }

        return $integrantes->count();
    }
}
