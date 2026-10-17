<?php

namespace App\Domain\Contactos;

use App\Enums\Rol;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

/** Contactos pendientes de revisar, acotados en la consulta a la unidad del dirigente (el admin ve la que elija). */
class ColaContactos
{
    /** @return Builder<Contacto> */
    public function __invoke(User $revisor, ?int $unidadId = null): Builder
    {
        if (! in_array($revisor->rol, [Rol::Dirigente, Rol::Admin], true)) {
            throw new AuthorizationException;
        }

        if (! $revisor->esAdmin()) {
            if ($revisor->unidad_id === null) {
                throw new AuthorizationException;
            }

            $unidadId = $revisor->unidad_id;
        }

        return Contacto::query()
            ->pendientes()
            ->with(['user:id,name,patrulla_id', 'patrulla:id,nombre'])
            ->when($unidadId, fn (Builder $q) => $q->where('unidad_id', $unidadId))
            ->orderBy('created_at')
            ->orderBy('id');
    }
}
