<?php

namespace App\Domain\Misiones;

use App\Domain\Ledger\AcreditarPatrulla;
use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Puntos;
use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Aprobar otorga siempre el puntaje fijo de la misión (§4.3). Tabla de acreditación (§4.5):
 *  - individual: +P puntos y +P LisCoins a la persona
 *  - por patrulla: +P puntos a la patrulla (una vez) y +⌈P/n⌉ LisCoins a cada integrante
 */
class AprobarEntrega
{
    public function __construct(private Puntos $puntos, private Jlis $jlis, private AcreditarPatrulla $acreditarPatrulla) {}

    /**
     * @throws AuthorizationException
     * @throws EntregaYaRevisada
     */
    public function __invoke(User $revisor, Entrega $entrega): Entrega
    {
        try {
            return DB::transaction(function () use ($revisor, $entrega) {
                $actual = Entrega::query()
                    ->with(['mision', 'user'])
                    ->whereKey($entrega->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                Gate::forUser($revisor)->authorize('revisar', $actual);

                if ($actual->estado !== EstadoEntrega::Pendiente) {
                    throw new EntregaYaRevisada($actual->estado);
                }

                $actual->forceFill([
                    'estado' => EstadoEntrega::Aprobada,
                    'revisado_por' => $revisor->getKey(),
                    'revisado_at' => now(),
                ])->save();

                $this->acreditar($actual, $revisor);

                return $actual;
            });
        } catch (UniqueConstraintViolationException) {
            // Ya hay una entrega aprobada para este sujeto y misión (idempotencia por base de datos).
            throw new EntregaYaRevisada(EstadoEntrega::Aprobada);
        }
    }

    private function acreditar(Entrega $entrega, User $revisor): void
    {
        $mision = $entrega->mision;
        $motivo = 'Misión aprobada: '.$mision->titulo;

        if (! $mision->esPorPatrulla()) {
            $this->puntos->individual($entrega->user, $mision->puntos, $entrega, $motivo, $revisor);
            $this->jlis->acreditar($entrega->user, $mision->puntos, $entrega, $motivo, $revisor);

            return;
        }

        ($this->acreditarPatrulla)($entrega->patrulla, $mision->puntos, $entrega, $motivo, $revisor);
    }
}
