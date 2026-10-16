<?php

namespace App\Domain\Actividades;

use App\Enums\Rol;
use App\Enums\TipoActividad;
use App\Models\Patrulla;
use App\Models\Reserva;
use App\Models\Turno;
use App\Models\Unidad;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Reserva un turno. En actividades individuales reserva la persona; en las de equipo, cualquier
 * integrante reserva por la patrulla (D2).
 *
 * Regla de no superposición (§5.4.5): ninguna persona puede tener dos reservas que se pisen,
 * sumando las suyas y las de su patrulla. Una reserva de patrulla se verifica contra las
 * individuales de todos sus integrantes (D3). Se corre dentro de una transacción que bloquea,
 * siempre en el mismo orden (turno → patrulla → personas por id), para que dos reservas
 * simultáneas no pasen ambas y no haya deadlocks.
 */
class Reservar
{
    /**
     * @throws AuthorizationException
     * @throws SinPatrulla
     * @throws TurnoYaComenzo
     * @throws ReservaDuplicada
     * @throws TurnoLleno
     * @throws ChoqueDeHorario
     * @throws EstacionEnCurso
     */
    public function __invoke(User $user, Turno $turno): Reserva
    {
        $actividad = $turno->actividad;

        Gate::forUser($user)->authorize('reservar', $actividad);

        $porEquipo = $actividad->esPorEquipo();
        $equipo = $porEquipo ? EquipoDeReserva::de($user) : null;

        if ($porEquipo && ! $equipo) {
            throw new SinPatrulla;
        }

        return DB::transaction(function () use ($user, $turno, $equipo) {
            $actual = Turno::query()->whereKey($turno->getKey())->lockForUpdate()->firstOrFail();

            if ($actual->haComenzado()) {
                throw new TurnoYaComenzo;
            }

            // Se bloquea siempre en el mismo orden (turno → equipo → personas por id) para que dos reservas simultáneas no se crucen.
            if (isset($equipo['patrulla_id'])) {
                Patrulla::query()->whereKey($equipo['patrulla_id'])->lockForUpdate()->firstOrFail();
                $personas = User::query()->where('patrulla_id', $equipo['patrulla_id'])->orderBy('id')->lockForUpdate()->pluck('id')->all();
            } elseif (isset($equipo['unidad_id'])) {
                Unidad::query()->whereKey($equipo['unidad_id'])->lockForUpdate()->firstOrFail();
                $personas = User::query()->where('unidad_id', $equipo['unidad_id'])->where('rol', Rol::Beneficiario)->orderBy('id')->lockForUpdate()->pluck('id')->all();
            } else {
                User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                $personas = [$user->getKey()];
            }

            $sujeto = $equipo ?? ['user_id' => $user->getKey()];

            // Una estación JOTA por equipo a la vez, sin importar el horario: hasta que termine la reservada no se puede reservar otra.
            if ($actual->actividad->esEstacion() && $equipo) {
                $enCurso = self::estacionVigente($equipo);

                if ($enCurso && $enCurso->turno_id !== $actual->getKey()) {
                    throw new EstacionEnCurso($enCurso->turno->actividad->titulo, $enCurso->turno->inicio, $enCurso->turno->fin);
                }
            }

            if (Reserva::query()->where('turno_id', $actual->getKey())->where($sujeto)->exists()) {
                throw new ReservaDuplicada;
            }

            if ($actual->cupo !== null && $actual->reservas()->count() >= $actual->cupo) {
                throw new TurnoLleno;
            }

            // Una persona de una unidad que va junta (Rovers) también choca con las reservas de su unidad.
            $unidadEquipo = $equipo['unidad_id'] ?? (EquipoDeReserva::unidadEsEquipo($user->unidad_id) ? $user->unidad_id : null);
            $choques = $this->choques($actual, $personas, $equipo['patrulla_id'] ?? $user->patrulla_id, $unidadEquipo);

            if ($choques !== []) {
                throw new ChoqueDeHorario($choques);
            }

            return Reserva::create(['turno_id' => $actual->getKey()] + $sujeto);
        });
    }

    /**
     * La estación que el equipo tiene reservada y todavía no terminó (su turno no pasó), si hay.
     *
     * @param  array{patrulla_id: int}|array{unidad_id: int}  $equipo
     */
    public static function estacionVigente(array $equipo): ?Reserva
    {
        return Reserva::query()
            ->select('reservas.*')
            ->join('turnos', 'turnos.id', '=', 'reservas.turno_id')
            ->join('actividades', 'actividades.id', '=', 'turnos.actividad_id')
            ->where('actividades.tipo', TipoActividad::EstacionJota)
            ->where('turnos.fin', '>', now())
            ->where($equipo)
            ->with('turno.actividad:id,titulo')
            ->orderBy('turnos.inicio')
            ->first();
    }

    /**
     * Reservas que se pisan con el turno (inicio < fin ajeno y fin > inicio ajeno; terminar justo
     * cuando otro empieza no es choque), de las personas dadas o de la patrulla.
     *
     * @param  list<int>  $personas
     * @return list<array{quien: string, actividad: string, inicio: CarbonInterface, fin: CarbonInterface}>
     */
    private function choques(Turno $turno, array $personas, ?int $patrullaId, ?int $unidadId = null): array
    {
        return Reserva::query()
            ->select('reservas.*')
            ->join('turnos', 'turnos.id', '=', 'reservas.turno_id')
            ->where('turnos.inicio', '<', $turno->fin)
            ->where('turnos.fin', '>', $turno->inicio)
            ->where(function ($q) use ($personas, $patrullaId, $unidadId) {
                $q->whereIn('reservas.user_id', $personas);

                if ($patrullaId !== null) {
                    $q->orWhere('reservas.patrulla_id', $patrullaId);
                }

                if ($unidadId !== null) {
                    $q->orWhere('reservas.unidad_id', $unidadId);
                }
            })
            ->with(['turno.actividad:id,titulo', 'user:id,name', 'patrulla:id,nombre', 'unidad:id,nombre'])
            ->get()
            ->map(fn (Reserva $r) => [
                'quien' => match (true) {
                    $r->user_id !== null => $r->user->name,
                    $r->patrulla_id !== null => 'la patrulla '.$r->patrulla->nombre,
                    default => 'toda la unidad '.$r->unidad->nombre,
                },
                'actividad' => $r->turno->actividad->titulo,
                'inicio' => $r->turno->inicio,
                'fin' => $r->turno->fin,
            ])
            ->all();
    }
}
