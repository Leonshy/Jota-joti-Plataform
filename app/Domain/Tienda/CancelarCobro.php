<?php

namespace App\Domain\Tienda;

use App\Models\Cobro;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * La tienda cancela un cobro que todavía nadie pagó: se lo hace vencer ahora, así el QR deja de servir.
 * Se bloquea el cobro (igual que al pagar), de modo que si alguien pagó justo antes, gana el pago.
 */
class CancelarCobro
{
    /**
     * @throws AuthorizationException quien no creó el cobro
     * @throws CobroYaPagado
     */
    public function __invoke(User $tienda, Cobro $cobro): Cobro
    {
        Gate::forUser($tienda)->authorize('crear', Cobro::class);

        return DB::transaction(function () use ($tienda, $cobro) {
            $actual = Cobro::query()->whereKey($cobro->getKey())->lockForUpdate()->firstOrFail();

            if ($actual->creado_por !== $tienda->getKey()) {
                throw new AuthorizationException;
            }

            if ($actual->pagado_at !== null) {
                throw new CobroYaPagado;
            }

            if ($actual->vence_at->isFuture()) {
                $actual->forceFill(['vence_at' => now()])->save();
            }

            return $actual;
        });
    }
}
