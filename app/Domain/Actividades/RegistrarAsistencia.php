<?php

namespace App\Domain\Actividades;

use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Puntos;
use App\Models\Asistencia;
use App\Models\SesionAsistencia;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Escaneo del QR: asistencia + acreditación inmediata (+P puntos y +P LisCoins), una vez por actividad. */
class RegistrarAsistencia
{
    public function __construct(private Puntos $puntos, private Jlis $jlis, private TokenRotativo $tokens) {}

    /**
     * @throws AuthorizationException
     * @throws SesionCerrada
     * @throws TokenInvalido
     * @throws AsistenciaYaRegistrada
     */
    public function __invoke(User $user, SesionAsistencia $sesion, string $token): Asistencia
    {
        $sesion = SesionAsistencia::query()->with('actividad')->findOrFail($sesion->getKey());
        $actividad = $sesion->actividad;

        Gate::forUser($user)->authorize('asistir', $actividad);

        if (! $sesion->estaAbierta()) {
            throw new SesionCerrada;
        }

        if (! $this->tokens->esValido($sesion, $token)) {
            throw new TokenInvalido;
        }

        try {
            return DB::transaction(function () use ($user, $sesion, $actividad) {
                // Primero el bloqueo exclusivo de la persona: el INSERT de la asistencia toma un
                // bloqueo compartido sobre su fila (clave foránea) y, si el ledger pidiera después el
                // exclusivo, varios escaneos simultáneos se cruzarían en un deadlock.
                User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

                // Único (actividad, persona): la base impide el doble registro aunque lleguen a la vez.
                $asistencia = Asistencia::create([
                    'actividad_id' => $actividad->getKey(),
                    'user_id' => $user->getKey(),
                    'sesion_id' => $sesion->getKey(),
                ]);

                if ($actividad->puntos > 0) { // D4: las actividades pueden valer 0
                    $motivo = 'Asistencia: '.$actividad->titulo;
                    $this->puntos->individual($user, $actividad->puntos, $asistencia, $motivo);
                    $this->jlis->acreditar($user, $actividad->puntos, $asistencia, $motivo);
                }

                return $asistencia;
            });
        } catch (UniqueConstraintViolationException) {
            throw new AsistenciaYaRegistrada;
        }
    }
}
