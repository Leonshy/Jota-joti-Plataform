<?php

namespace App\Domain\Penalizaciones;

use App\Domain\Ledger\Puntos;
use App\Enums\Rol;
use App\Models\Patrulla;
use App\Models\Penalizacion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Penalización a una persona o a una patrulla (§4.5): resta puntos con motivo obligatorio y NO
 * toca los LisCoins. La aplica el dirigente de la unidad o el administrador. Los puntos pueden
 * quedar negativos (D14).
 */
class Penalizar
{
    public const MAXIMO = 1_000_000;

    public function __construct(private Puntos $puntos) {}

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function persona(User $autor, User $persona, int $cantidad, string $motivo): Penalizacion
    {
        Gate::forUser($autor)->authorize('gestionar', $persona);
        $this->validar($cantidad, $motivo);

        if ($persona->rol !== Rol::Beneficiario) {
            throw new InvalidArgumentException('Solo se penaliza a participantes.');
        }

        return DB::transaction(function () use ($autor, $persona, $cantidad, $motivo) {
            // Se bloquea al objetivo ANTES de insertar: el INSERT toma un bloqueo compartido sobre su
            // fila (clave foránea) y pedir después el exclusivo cruzaría transacciones en un deadlock.
            User::query()->whereKey($persona->getKey())->lockForUpdate()->firstOrFail();

            $penalizacion = Penalizacion::create([
                'user_id' => $persona->getKey(),
                'cantidad' => $cantidad,
                'motivo' => trim($motivo),
                'registrado_por' => $autor->getKey(),
            ]);

            $this->puntos->individual($persona, -$cantidad, $penalizacion, 'Penalización: '.trim($motivo), $autor);

            return $penalizacion;
        });
    }

    public function patrulla(User $autor, Patrulla $patrulla, int $cantidad, string $motivo): Penalizacion
    {
        Gate::forUser($autor)->authorize('penalizar', $patrulla);
        $this->validar($cantidad, $motivo);

        return DB::transaction(function () use ($autor, $patrulla, $cantidad, $motivo) {
            Patrulla::query()->whereKey($patrulla->getKey())->lockForUpdate()->firstOrFail();

            $penalizacion = Penalizacion::create([
                'patrulla_id' => $patrulla->getKey(),
                'cantidad' => $cantidad,
                'motivo' => trim($motivo),
                'registrado_por' => $autor->getKey(),
            ]);

            $this->puntos->patrulla($patrulla, -$cantidad, $penalizacion, 'Penalización: '.trim($motivo), $autor);

            return $penalizacion;
        });
    }

    private function validar(int $cantidad, string $motivo): void
    {
        if ($cantidad <= 0 || $cantidad > self::MAXIMO) {
            throw new InvalidArgumentException('La cantidad tiene que ser mayor a cero y no pasar de '.number_format(self::MAXIMO, 0, ',', '.').' puntos.');
        }

        if (trim($motivo) === '') {
            throw new InvalidArgumentException('Para penalizar tenés que indicar el motivo.');
        }
    }
}
