<?php

namespace App\Domain\Misiones;

use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RechazarEntrega
{
    /**
     * @throws AuthorizationException
     * @throws EntregaInvalida sin motivo
     * @throws EntregaYaRevisada
     */
    public function __invoke(User $revisor, Entrega $entrega, string $motivo): Entrega
    {
        $motivo = trim($motivo);

        return DB::transaction(function () use ($revisor, $entrega, $motivo) {
            $actual = Entrega::query()->with('user')->whereKey($entrega->getKey())->lockForUpdate()->firstOrFail();

            Gate::forUser($revisor)->authorize('revisar', $actual);

            if ($motivo === '') {
                throw new EntregaInvalida('Para rechazar tenés que indicar el motivo.');
            }

            if ($actual->estado !== EstadoEntrega::Pendiente) {
                throw new EntregaYaRevisada($actual->estado);
            }

            $actual->forceFill([
                'estado' => EstadoEntrega::Rechazada,
                'motivo_rechazo' => $motivo,
                'revisado_por' => $revisor->getKey(),
                'revisado_at' => now(),
            ])->save();

            return $actual;
        });
    }
}
