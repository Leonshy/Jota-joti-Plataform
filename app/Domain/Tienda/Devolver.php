<?php

namespace App\Domain\Tienda;

use App\Domain\Ledger\Jlis;
use App\Enums\Rol;
use App\Models\Cobro;
use App\Models\Devolucion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Devolución de LisCoins a un participante, con motivo (§4.6). Solo suma LisCoins: no toca puntos.
 * Tope: no se devuelve más de lo que la persona gastó en la tienda y no se le devolvió.
 */
class Devolver
{
    public function __construct(private Jlis $jlis) {}

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     * @throws DevolucionExcedeCompras
     */
    public function __invoke(User $tienda, User $participante, int $monto, string $motivo): Devolucion
    {
        Gate::forUser($tienda)->authorize('devolver', Cobro::class);

        $motivo = trim($motivo);

        if ($monto <= 0) {
            throw new InvalidArgumentException('El monto a devolver tiene que ser mayor a cero.');
        }

        if ($motivo === '') {
            throw new InvalidArgumentException('Indicá el motivo de la devolución.');
        }

        if ($participante->rol !== Rol::Beneficiario) {
            throw new InvalidArgumentException('Solo se devuelven LisCoins a participantes.');
        }

        return DB::transaction(function () use ($tienda, $participante, $monto, $motivo) {
            // Se bloquea a la persona: dos devoluciones simultáneas no pasan ambas el tope.
            User::query()->whereKey($participante->getKey())->lockForUpdate()->firstOrFail();

            $maximo = (new ResumenCompras)($participante)['maximo'];

            if ($monto > $maximo) {
                throw new DevolucionExcedeCompras($maximo);
            }

            $devolucion = Devolucion::create([
                'user_id' => $participante->getKey(),
                'monto' => $monto,
                'motivo' => $motivo,
                'registrado_por' => $tienda->getKey(),
            ]);

            $this->jlis->acreditar($participante, $monto, $devolucion, 'Devolución: '.$motivo, $tienda);

            return $devolucion;
        });
    }
}
