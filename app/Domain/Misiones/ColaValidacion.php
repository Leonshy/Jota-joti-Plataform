<?php

namespace App\Domain\Misiones;

use App\Enums\Rol;
use App\Models\Entrega;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Entregas pendientes de revisar, acotadas en la consulta (no en la vista) a la unidad del
 * dirigente. El admin ve todas, o las de una unidad si la indica. Las más antiguas primero.
 */
class ColaValidacion
{
    /** @return Builder<Entrega> */
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

        return Entrega::query()
            ->pendientes()
            ->with(['mision:id,titulo,puntos,modalidad', 'user:id,name,unidad_id,patrulla_id', 'patrulla:id,nombre', 'imagenes'])
            ->when($unidadId, fn (Builder $q) => $q->whereHas('user', fn (Builder $u) => $u->where('unidad_id', $unidadId)))
            ->orderBy('created_at')
            ->orderBy('id');
    }
}
