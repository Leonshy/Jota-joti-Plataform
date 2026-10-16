<?php

namespace App\Domain\Tienda;

use App\Models\Cobro;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/** La tienda ingresa el monto y se genera un cobro de un solo uso con vencimiento corto. */
class CrearCobro
{
    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function __invoke(User $tienda, int $monto): Cobro
    {
        Gate::forUser($tienda)->authorize('crear', Cobro::class);

        if ($monto <= 0 || $monto > config('jota.tienda.monto_maximo')) {
            throw new InvalidArgumentException('El monto tiene que ser mayor a cero y no pasar de '.number_format(config('jota.tienda.monto_maximo'), 0, ',', '.').' LisCoins.');
        }

        return Cobro::create([
            'token' => bin2hex(random_bytes(32)), // 256 bits: no se puede adivinar
            'monto' => $monto,
            'creado_por' => $tienda->getKey(),
            'vence_at' => now()->addSeconds(config('jota.tienda.vigencia_segundos')),
        ]);
    }
}
