<?php

namespace App\Domain\Contactos;

use App\Enums\EstadoEntrega;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Un contacto rechazado queda rechazado: no se puede volver a enviar ni revisar (decisión del grupo). */
class RechazarContacto
{
    /**
     * @throws AuthorizationException
     * @throws ContactoInvalido sin motivo
     * @throws ContactoYaRevisado
     */
    public function __invoke(User $revisor, Contacto $contacto, string $motivo): Contacto
    {
        $motivo = trim($motivo);

        return DB::transaction(function () use ($revisor, $contacto, $motivo) {
            $actual = Contacto::query()->whereKey($contacto->getKey())->lockForUpdate()->firstOrFail();

            Gate::forUser($revisor)->authorize('revisar', $actual);

            if ($motivo === '') {
                throw new ContactoInvalido('Para rechazar tenés que indicar el motivo.');
            }

            if ($actual->estado !== EstadoEntrega::Pendiente) {
                throw new ContactoYaRevisado($actual->estado);
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
