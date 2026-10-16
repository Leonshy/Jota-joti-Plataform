<?php

namespace App\Domain\Tienda;

use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\SaldoInsuficiente;
use App\Models\Cobro;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Pago de un cobro (§5.4.8), en una sola transacción: bloquear al participante → bloquear el
 * cobro → verificar que no esté pagado ni vencido → verificar saldo ≥ monto (el ledger no deja
 * pasar un saldo negativo) → insertar el movimiento −M → marcar como pagado.
 *
 * El orden de bloqueo es siempre persona → cobro → ledger. El saldo lo protege el bloqueo de la
 * persona (el mismo participante pagando varios cobros a la vez se encola) y el cobro se paga una
 * sola vez por el bloqueo del cobro y el UPDATE condicional. El pago solo toca LisCoins, nunca puntos.
 */
class PagarCobro
{
    public function __construct(private Jlis $jlis) {}

    /**
     * @throws AuthorizationException
     * @throws CobroNoExiste
     * @throws CobroYaPagado
     * @throws CobroVencido
     * @throws SaldoInsuficiente
     */
    public function __invoke(User $pagador, string $token): Cobro
    {
        Gate::forUser($pagador)->authorize('pagar', Cobro::class);

        return DB::transaction(function () use ($pagador, $token) {
            User::query()->whereKey($pagador->getKey())->lockForUpdate()->firstOrFail();

            $cobro = Cobro::query()->where('token', $token)->lockForUpdate()->first();

            if (! $cobro) {
                throw new CobroNoExiste;
            }

            if ($cobro->pagado_at !== null) {
                throw new CobroYaPagado;
            }

            if ($cobro->vence_at->lte(now())) {
                throw new CobroVencido;
            }

            $this->jlis->debitar($pagador, $cobro->monto, $cobro, 'Compra en la tienda', $pagador);

            // UPDATE condicional: aunque algo fallara arriba, un cobro se paga una sola vez.
            $pagados = Cobro::query()->whereKey($cobro->getKey())->whereNull('pagado_at')
                ->update(['pagado_por' => $pagador->getKey(), 'pagado_at' => now()]);

            if ($pagados !== 1) {
                throw new CobroYaPagado;
            }

            return $cobro->refresh();
        });
    }
}
