<?php

namespace App\Domain\Actividades;

use App\Models\Actividad;
use App\Models\SesionAsistencia;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Gate;

class AbrirSesionAsistencia
{
    /**
     * @throws AuthorizationException
     * @throws ModalidadIncorrecta actividad por equipo (no registran asistencia)
     * @throws SesionYaAbierta
     */
    public function __invoke(User $dirigente, Actividad $actividad, ?Turno $turno = null): SesionAsistencia
    {
        Gate::forUser($dirigente)->authorize('gestionar', $actividad);

        if ($actividad->esPorEquipo()) {
            throw new ModalidadIncorrecta('Las actividades por equipo no registran asistencia: se marca el cumplimiento de cada patrulla.');
        }

        if ($turno && $turno->actividad_id !== $actividad->getKey()) {
            throw new ModalidadIncorrecta('Ese turno no es de esta actividad.');
        }

        try {
            return SesionAsistencia::create([
                'actividad_id' => $actividad->getKey(),
                'turno_id' => $turno?->getKey(),
                'abierta_por' => $dirigente->getKey(),
                'secreto' => bin2hex(random_bytes(32)),
                'abierta_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Una sola sesión abierta por actividad (índice único en base).
            throw new SesionYaAbierta(
                SesionAsistencia::query()->where('actividad_id', $actividad->getKey())->whereNull('cerrada_at')->first()
            );
        }
    }
}
