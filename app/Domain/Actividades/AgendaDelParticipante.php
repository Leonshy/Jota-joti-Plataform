<?php

namespace App\Domain\Actividades;

use App\Models\Actividad;
use App\Models\Reserva;
use App\Models\Turno;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lo que el participante ve en la Agenda: las actividades de su unidad con los turnos que todavía
 * no terminaron, cuántos lugares quedan y cuáles tiene reservados (propios o de su patrulla).
 * Solo lectura; la hora que vale es la del servidor.
 */
class AgendaDelParticipante
{
    /**
     * @return list<array{
     *     id: int, titulo: string, lugar: string|null, taller: bool, por_equipo: bool, puntos: int, apuntado: bool,
     *     turnos: list<array{id: int, inicio: CarbonInterface, fin: CarbonInterface, lugares: int|null, comenzado: bool, cerrado: bool, reservado: bool}>
     * }>
     */
    public function actividades(User $user): array
    {
        // Una sola consulta: los turnos vigentes reservados y las actividades en las que ya hay un turno (aunque haya pasado).
        $propias = $this->reservasPropias($user);
        $reservados = $propias->where('vigente', 1)->pluck('turno_id')->map(fn ($id) => (int) $id)->all();
        $apuntadas = $propias->pluck('actividad_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        $lista = Actividad::query()
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
                // Un taller se cursa una sola vez: si ya hay un turno elegido (aunque haya pasado), los demás no se pueden elegir.
                'apuntado' => in_array($a->getKey(), $apuntadas, true),
                'turnos' => $a->turnos->map(fn (Turno $t) => [
                    'id' => $t->getKey(),
                    'inicio' => $t->inicio,
                    'fin' => $t->fin,
                    'lugares' => $t->cupo === null ? null : max(0, $t->cupo - $t->reservas_count),
                    'comenzado' => $t->haComenzado(),
                    // Un taller admite anotarse hasta que termina (y acá solo se listan turnos que no terminaron); la estación, hasta que empieza.
                    'cerrado' => $a->esEstacion() && $t->haComenzado(),
                    'reservado' => in_array($t->getKey(), $reservados, true),
                ])->all(),
            ])
            ->all();

        return $this->ordenar($lista);
    }

    /**
     * Orden y filtro que pidió el grupo (17/10/2026). Primero los talleres y después las estaciones JOTA, y en cada grupo:
     *  - TALLERES: en curso → con lugar → apuntado → lleno.
     *  - ESTACIONES: reservado → disponible → en curso (de mi equipo). No se muestran las ocupadas por otro equipo, las bloqueadas
     *    (mi equipo ya tiene otra) ni las que ya empezaron y no son de mi equipo.
     * El orden de las actividades sale de su mejor turno; el de los turnos, del mismo criterio y después por horario.
     *
     * @param  list<array<string, mixed>>  $lista
     * @return list<array<string, mixed>>
     */
    private function ordenar(array $lista): array
    {
        $tieneEstacion = collect($lista)->contains(fn ($a) => ! $a['taller'] && collect($a['turnos'])->contains('reservado', true));

        $rango = [
            'en_curso_taller' => 0, 'con_lugar' => 1, 'apuntado' => 2, 'lleno' => 3,
            'reservado' => 0, 'disponible' => 1, 'en_curso_estacion' => 2,
        ];

        return collect($lista)
            ->map(function (array $a) use ($tieneEstacion, $rango) {
                $turnos = collect($a['turnos']);

                if (! $a['taller']) {
                    $turnos = $turnos->filter(fn ($t) => $t['reservado'] || (! $t['comenzado'] && $t['lugares'] !== 0 && ! $tieneEstacion));
                }

                $turnos = $turnos->map(function ($t) use ($a) {
                    $estado = $a['taller']
                        ? match (true) {
                            $t['reservado'] => 'apuntado',
                            $t['lugares'] === 0 => 'lleno',
                            $t['comenzado'] => 'en_curso',
                            default => 'con_lugar',
                        }
                    : match (true) {
                        $t['reservado'] && $t['comenzado'] => 'en_curso',
                        $t['reservado'] => 'reservado',
                        default => 'disponible',
                    };

                    return $t + ['estado' => $estado];
                });

                $clave = fn (array $t) => $rango[$t['estado'] === 'en_curso' ? ($a['taller'] ? 'en_curso_taller' : 'en_curso_estacion') : $t['estado']];

                // Dentro de una actividad en la que ya estoy apuntado, mi turno va arriba; si no, el criterio de siempre y después el horario.
                $turnos = $turnos->sortBy([
                    fn ($x, $y) => ($x['estado'] === 'apuntado' ? -1 : $clave($x)) <=> ($y['estado'] === 'apuntado' ? -1 : $clave($y)),
                    fn ($x, $y) => $x['inicio'] <=> $y['inicio'],
                ])->values();

                // Rango de la actividad: apuntado cuenta como «apuntado» aunque sus otros turnos estén bloqueados.
                $propios = $turnos->reject(fn ($t) => $t['estado'] === 'apuntado');
                $a['rango'] = match (true) {
                    $a['taller'] && ($a['apuntado'] || $turnos->contains('estado', 'apuntado')) => $rango['apuntado'],
                    $turnos->isEmpty() => 99,
                    default => $propios->map($clave)->min() ?? 99,
                };
                $a['turnos'] = $turnos->all();

                return $a;
            })
            // Una estación sin turnos que mostrar no aparece. Un taller sin turnos sí (se avisa que no tiene turnos).
            ->reject(fn ($a) => ! $a['taller'] && $a['turnos'] === [])
            ->sortBy([
                fn ($x, $y) => $y['taller'] <=> $x['taller'],
                fn ($x, $y) => $x['rango'] <=> $y['rango'],
                fn ($x, $y) => $x['titulo'] <=> $y['titulo'],
            ])
            ->values()
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

    /** @return Collection<int, Reserva> con turno_id, actividad_id y vigente (1 si el turno todavía no terminó). */
    private function reservasPropias(User $user): Collection
    {
        return $this->consultaReservas($user, soloVigentes: false)
            ->reorder()
            ->select('reservas.turno_id', 'turnos.actividad_id')
            ->selectRaw('(turnos.fin > ?) as vigente', [now()])
            ->get();
    }

    private function consultaReservas(User $user, bool $soloVigentes = true): Builder
    {
        return Reserva::query()
            ->select('reservas.*')
            ->join('turnos', 'turnos.id', '=', 'reservas.turno_id')
            ->when($soloVigentes, fn (Builder $q) => $q->where('turnos.fin', '>', now()))
            ->where(fn (Builder $q) => $q
                ->where('reservas.user_id', $user->getKey())
                ->when($user->patrulla_id, fn (Builder $p) => $p->orWhere('reservas.patrulla_id', $user->patrulla_id))
                // Los Rovers hacen las actividades por equipo todos juntos: la reserva es de la unidad.
                ->when(EquipoDeReserva::unidadEsEquipo($user->unidad_id), fn (Builder $p) => $p->orWhere('reservas.unidad_id', $user->unidad_id)))
            ->orderBy('turnos.inicio');
    }
}
