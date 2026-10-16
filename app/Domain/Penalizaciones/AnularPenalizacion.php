<?php

namespace App\Domain\Penalizaciones;

use App\Domain\Ledger\Puntos;
use App\Models\Patrulla;
use App\Models\Penalizacion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Corrige una penalización mal puesta. El ledger es de solo agregar (CLAUDE.md §2.1): no se
 * borra nada, se suma un movimiento de signo contrario con motivo y la penalización queda marcada.
 */
class AnularPenalizacion
{
    public function __construct(private Puntos $puntos) {}

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException sin motivo
     * @throws PenalizacionYaAnulada
     */
    public function __invoke(User $autor, Penalizacion $penalizacion, string $motivo): Penalizacion
    {
        $motivo = trim($motivo);

        return DB::transaction(function () use ($autor, $penalizacion, $motivo) {
            $actual = Penalizacion::query()->whereKey($penalizacion->getKey())->lockForUpdate()->firstOrFail();

            Gate::forUser($autor)->authorize('anular', $actual);

            if ($motivo === '') {
                throw new InvalidArgumentException('Para anular tenés que indicar el motivo.');
            }

            if ($actual->estaAnulada()) {
                throw new PenalizacionYaAnulada;
            }

            // Mismo orden que al penalizar: objetivo primero, después las filas nuevas.
            if ($actual->user_id) {
                User::query()->whereKey($actual->user_id)->lockForUpdate()->firstOrFail();
            } else {
                Patrulla::query()->whereKey($actual->patrulla_id)->lockForUpdate()->firstOrFail();
            }

            $actual->forceFill([
                'anulada_at' => now(),
                'anulada_por' => $autor->getKey(),
                'motivo_anulacion' => $motivo,
            ])->save();

            $texto = 'Anulación de penalización: '.$motivo;

            if ($actual->user_id) {
                $this->puntos->individual($actual->user, $actual->cantidad, $actual, $texto, $autor);
            } else {
                $this->puntos->patrulla($actual->patrulla, $actual->cantidad, $actual, $texto, $autor);
            }

            return $actual;
        });
    }
}
