<?php

namespace App\Domain\Actividades;

use App\Enums\Rol;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reservas vigentes (turnos que no terminaron) que un dirigente puede gestionar: las de personas y
 * patrullas de su unidad, acotadas en la consulta y no en la vista (CLAUDE.md §2.7).
 */
class ReservasDeUnidad
{
    /** @return Builder<Reserva> */
    public function __invoke(User $dirigente, ?string $buscar = null, ?int $unidadId = null): Builder
    {
        // El administrador indica la unidad; el dirigente siempre la suya.
        $unidad = match ($dirigente->rol) {
            Rol::Dirigente => $dirigente->unidad_id,
            Rol::Admin => $unidadId,
            default => null,
        };

        if ($unidad === null) {
            throw new AuthorizationException;
        }

        $buscar = trim((string) $buscar);

        return Reserva::query()
            ->select('reservas.*')
            ->join('turnos', 'turnos.id', '=', 'reservas.turno_id')
            ->join('actividades', 'actividades.id', '=', 'turnos.actividad_id')
            ->where('turnos.fin', '>', now())
            ->where(fn (Builder $q) => $q
                ->whereHas('user', fn (Builder $u) => $u->where('unidad_id', $unidad))
                ->orWhereHas('patrulla', fn (Builder $p) => $p->where('unidad_id', $unidad))
                ->orWhere('reservas.unidad_id', $unidad))
            ->when($buscar !== '', fn (Builder $q) => $q->where(fn (Builder $b) => $b
                ->whereHas('user', fn (Builder $u) => $u->where('name', 'like', '%'.addcslashes($buscar, '%_\\').'%'))
                ->orWhereHas('patrulla', fn (Builder $p) => $p->where('nombre', 'like', '%'.addcslashes($buscar, '%_\\').'%'))
                ->orWhereHas('unidad', fn (Builder $x) => $x->where('nombre', 'like', '%'.addcslashes($buscar, '%_\\').'%'))))
            ->with(['turno.actividad:id,titulo', 'user:id,name,patrulla_id', 'user.patrulla:id,nombre', 'patrulla:id,nombre', 'unidad:id,nombre'])
            ->orderBy('turnos.inicio')
            ->orderBy('actividades.titulo')
            ->orderBy('reservas.id');
    }
}
