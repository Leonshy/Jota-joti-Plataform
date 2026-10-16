<?php

namespace App\Domain\Inicio;

use App\Domain\Actividades\EquipoDeReserva;
use App\Enums\EstadoEntrega;
use App\Enums\Modalidad;
use App\Models\Mision;
use App\Models\Reserva;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lo que muestra el Inicio del participante: saldos, su patrulla, cuántas misiones tiene para entregar
 * y su próximo turno. Solo lectura, en pocas consultas, siempre con la hora del servidor.
 */
class ResumenParticipante
{
    /**
     * @return array{
     *     puntos: int, jlis: int,
     *     patrulla: array{nombre: string, puntos: int}|null,
     *     misiones_por_entregar: int,
     *     proximo_turno: array{actividad: string, inicio: CarbonInterface, por_patrulla: bool}|null
     * }
     */
    public function __invoke(User $user): array
    {
        $user = $user->fresh(['patrulla:id,nombre,puntos']);

        return [
            'puntos' => $user->puntos,
            'jlis' => $user->jlis,
            'patrulla' => $user->patrulla ? ['nombre' => $user->patrulla->nombre, 'puntos' => $user->patrulla->puntos] : null,
            'misiones_por_entregar' => $this->misionesPorEntregar($user),
            'proximo_turno' => $this->proximoTurno($user),
        ];
    }

    /**
     * Misiones ya lanzadas, de su unidad y abiertas, que todavía puede entregar: sin una entrega pendiente
     * ni aprobada de la persona (individuales) o de su patrulla (por patrulla).
     */
    private function misionesPorEntregar(User $user): int
    {
        $entregada = fn (Builder $q, string $columna, int $id) => $q
            ->where($columna, $id)
            ->whereIn('estado', [EstadoEntrega::Pendiente, EstadoEntrega::Aprobada]);

        return Mision::query()
            ->visiblesPara($user)
            ->whereNull('cerrada_at')
            ->where(fn (Builder $q) => $q->whereNull('vence_at')->orWhere('vence_at', '>', now()))
            ->where(function (Builder $q) use ($user, $entregada) {
                $q->where(fn (Builder $i) => $i
                    ->where('modalidad', Modalidad::Individual)
                    ->whereDoesntHave('entregas', fn (Builder $e) => $entregada($e, 'user_id', $user->getKey())));

                // Una misión por patrulla solo se puede entregar si se pertenece a una.
                if ($user->patrulla_id) {
                    $q->orWhere(fn (Builder $p) => $p
                        ->where('modalidad', Modalidad::Patrulla)
                        ->whereDoesntHave('entregas', fn (Builder $e) => $entregada($e, 'patrulla_id', $user->patrulla_id)));
                }
            })
            ->count();
    }

    private function proximoTurno(User $user): ?array
    {
        $reserva = Reserva::query()
            ->select('reservas.*')
            ->join('turnos', 'turnos.id', '=', 'reservas.turno_id')
            ->where('turnos.inicio', '>', now())
            ->where(function (Builder $q) use ($user) {
                $q->where('reservas.user_id', $user->getKey());

                if ($user->patrulla_id) {
                    $q->orWhere('reservas.patrulla_id', $user->patrulla_id);
                }

                if (EquipoDeReserva::unidadEsEquipo($user->unidad_id)) {
                    $q->orWhere('reservas.unidad_id', $user->unidad_id);
                }
            })
            ->with('turno.actividad:id,titulo')
            ->orderBy('turnos.inicio')
            ->first();

        return $reserva ? [
            'actividad' => $reserva->turno->actividad->titulo,
            'inicio' => $reserva->turno->inicio,
            'por_patrulla' => $reserva->user_id === null,
        ] : null;
    }
}
