<?php

namespace App\Domain\Actividades;

use App\Models\Actividad;
use App\Models\Reserva;
use App\Models\Turno;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lo que el participante ve en la Agenda: las actividades de su unidad con los turnos que todavía
 * no terminaron, cuántos lugares quedan y cuáles tiene reservados (propios o de su patrulla).
 * Solo lectura; la hora que vale es la del servidor.
 */
class AgendaDelParticipante
{
    /**
     * @return list<array{
     *     id: int, titulo: string, lugar: string|null, taller: bool, por_equipo: bool, puntos: int,
     *     turnos: list<array{id: int, inicio: CarbonInterface, fin: CarbonInterface, lugares: int|null, comenzado: bool, reservado: bool}>
     * }>
     */
    public function actividades(User $user): array
    {
        $reservados = $this->turnosReservados($user);

        return Actividad::query()
            ->whereHas('unidades', fn (Builder $q) => $q->whereKey($user->unidad_id))
            ->with(['turnos' => fn ($q) => $q->where('fin', '>', now())->withCount('reservas')->orderBy('inicio')])
            ->orderBy('titulo')
            ->get()
            ->map(fn (Actividad $a) => [
                'id' => $a->getKey(),
                'titulo' => $a->titulo,
                'lugar' => $a->lugar,
                'taller' => $a->tipo->value === 'taller',
                'por_equipo' => $a->esPorEquipo(),
                'puntos' => $a->puntos,
                'turnos' => $a->turnos->map(fn (Turno $t) => [
                    'id' => $t->getKey(),
                    'inicio' => $t->inicio,
                    'fin' => $t->fin,
                    'lugares' => $t->cupo === null ? null : max(0, $t->cupo - $t->reservas_count),
                    'comenzado' => $t->haComenzado(),
                    'reservado' => in_array($t->getKey(), $reservados, true),
                ])->all(),
            ])
            ->all();
    }

    /**
     * La estación JOTA que el equipo de la persona tiene reservada y todavía no terminó (solo se puede una a la vez).
     *
     * @return array{actividad: string, lugar: string|null, inicio: CarbonInterface, fin: CarbonInterface}|null
     */
    public function estacionReservada(User $user): ?array
    {
        $equipo = EquipoDeReserva::de($user);
        $reserva = $equipo ? Reservar::estacionVigente($equipo) : null;

        return $reserva ? ['actividad' => $reserva->turno->actividad->titulo, 'lugar' => $reserva->turno->actividad->lugar, 'inicio' => $reserva->turno->inicio, 'fin' => $reserva->turno->fin] : null;
    }

    /**
     * Reservas vigentes (turnos que no terminaron), las más próximas primero.
     *
     * @return list<array{id: int, actividad: string, lugar: string|null, inicio: CarbonInterface, fin: CarbonInterface, por_equipo: bool, comenzado: bool}>
     */
    public function reservas(User $user): array
    {
        return $this->consultaReservas($user)
            ->with('turno.actividad:id,titulo,lugar')
            ->get()
            ->map(fn (Reserva $r) => [
                'id' => $r->getKey(),
                'actividad' => $r->turno->actividad->titulo,
                'lugar' => $r->turno->actividad->lugar,
                'inicio' => $r->turno->inicio,
                'fin' => $r->turno->fin,
                'por_equipo' => $r->user_id === null,
                'comenzado' => $r->turno->haComenzado(),
            ])
            ->all();
    }

    /** @return list<int> */
    private function turnosReservados(User $user): array
    {
        return $this->consultaReservas($user)->pluck('reservas.turno_id')->all();
    }

    private function consultaReservas(User $user): Builder
    {
        return Reserva::query()
            ->select('reservas.*')
            ->join('turnos', 'turnos.id', '=', 'reservas.turno_id')
            ->where('turnos.fin', '>', now())
            ->where(fn (Builder $q) => $q
                ->where('reservas.user_id', $user->getKey())
                ->when($user->patrulla_id, fn (Builder $p) => $p->orWhere('reservas.patrulla_id', $user->patrulla_id))
                // Los Rovers hacen las actividades por equipo todos juntos: la reserva es de la unidad.
                ->when(EquipoDeReserva::unidadEsEquipo($user->unidad_id), fn (Builder $p) => $p->orWhere('reservas.unidad_id', $user->unidad_id)))
            ->orderBy('turnos.inicio');
    }
}
