<?php

namespace App\Domain\Actividades;

use App\Domain\Ledger\AcreditarPatrulla;
use App\Domain\Ledger\AcreditarUnidad;
use App\Models\Actividad;
use App\Models\CumplimientoEquipo;
use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Actividades por equipo: el dirigente marca cada equipo como cumplido / no cumplido.
 *
 *  - Tropa y Caminantes (por patrulla): al marcarla cumplida se acreditan +P a la patrulla (una vez) y ⌈P/n⌉ LisCoins a cada integrante.
 *  - Rovers (el equipo es toda la unidad): cada Rover recibe +P puntos y +P LisCoins.
 *
 * Transiciones: (sin marca) → cumplida | no cumplida, y no cumplida → cumplida. Un equipo ya cumplido no se vuelve a acreditar
 * ni se revierte desde acá: las correcciones son movimientos nuevos con motivo (CLAUDE.md §2.1).
 */
class MarcarCumplimiento
{
    public function __construct(private AcreditarPatrulla $acreditarPatrulla, private AcreditarUnidad $acreditarUnidad) {}

    /**
     * @param  Patrulla|Unidad  $equipo  una patrulla, o la unidad entera si no se arma por patrulla
     *
     * @throws AuthorizationException
     * @throws ModalidadIncorrecta
     * @throws PatrullaFueraDeActividad
     * @throws CumplimientoYaAcreditado
     */
    public function __invoke(User $marcador, Actividad $actividad, Patrulla|Unidad $equipo, bool $cumplida): CumplimientoEquipo
    {
        Gate::forUser($marcador)->authorize('gestionar', $actividad);
        Gate::forUser($marcador)->authorize('gestionar', $equipo); // el dirigente solo sobre su unidad

        if (! $actividad->esPorEquipo()) {
            throw new ModalidadIncorrecta('Esta actividad es individual: se registra con el QR de asistencia.');
        }

        $unidadId = $equipo instanceof Unidad ? $equipo->getKey() : $equipo->unidad_id;

        if (! $actividad->esParaUnidad($unidadId)) {
            throw new PatrullaFueraDeActividad;
        }

        if ($equipo instanceof Unidad && ! EquipoDeReserva::unidadEsEquipo($equipo->getKey())) {
            throw new ModalidadIncorrecta('Esta unidad se arma por patrulla: se marca cada patrulla.');
        }

        $columna = $equipo instanceof Unidad ? 'unidad_id' : 'patrulla_id';

        try {
            return DB::transaction(function () use ($marcador, $actividad, $equipo, $columna, $cumplida) {
                // Se bloquea el equipo: dos marcas simultáneas se serializan.
                $equipo::query()->whereKey($equipo->getKey())->lockForUpdate()->firstOrFail();

                $existente = CumplimientoEquipo::query()
                    ->where(['actividad_id' => $actividad->getKey(), $columna => $equipo->getKey()])
                    ->first();

                if ($existente?->cumplida) {
                    throw new CumplimientoYaAcreditado;
                }

                if ($existente && ! $cumplida) {
                    return $existente; // sigue como no cumplida
                }

                $registro = $existente ?? new CumplimientoEquipo([
                    'actividad_id' => $actividad->getKey(),
                    $columna => $equipo->getKey(),
                ]);
                $registro->fill(['cumplida' => $cumplida, 'marcado_por' => $marcador->getKey()])->save();

                if ($cumplida) {
                    $motivo = 'Actividad cumplida: '.$actividad->titulo;

                    $equipo instanceof Unidad
                        ? ($this->acreditarUnidad)($equipo, $actividad->puntos, $registro, $motivo, $marcador)
                        : ($this->acreditarPatrulla)($equipo, $actividad->puntos, $registro, $motivo, $marcador);
                }

                return $registro;
            });
        } catch (UniqueConstraintViolationException) {
            throw new CumplimientoYaAcreditado;
        }
    }
}
