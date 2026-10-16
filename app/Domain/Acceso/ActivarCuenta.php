<?php

namespace App\Domain\Acceso;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Primer ingreso: con solo el CI, la persona crea su PIN de 4 dígitos. */
class ActivarCuenta
{
    public const LARGO_PIN = 4;

    /**
     * Paso 1 de la activación: ¿ese CI está cargado y todavía sin PIN? No cambia nada.
     *
     * @throws CiNoRegistrado
     * @throws CuentaYaActivada
     */
    public function verificar(string $ci): User
    {
        $user = User::query()->where('ci', trim($ci))->first() ?? throw new CiNoRegistrado;

        if ($user->activado_at) {
            throw new CuentaYaActivada;
        }

        return $user;
    }

    /**
     * @throws InvalidArgumentException PIN que no son 4 dígitos
     * @throws CiNoRegistrado
     * @throws CuentaYaActivada
     */
    public function __invoke(string $ci, string $pin): User
    {
        if (! preg_match('/^\d{'.self::LARGO_PIN.'}$/', $pin)) {
            throw new InvalidArgumentException('El PIN debe tener '.self::LARGO_PIN.' dígitos.');
        }

        // Fila bloqueada y estado re-verificado: dos activaciones simultáneas del mismo CI
        // no pasan las dos (gana la primera).
        return DB::transaction(function () use ($ci, $pin) {
            $user = User::query()->where('ci', trim($ci))->lockForUpdate()->first();

            if (! $user) {
                throw new CiNoRegistrado;
            }

            if ($user->activado_at) {
                throw new CuentaYaActivada;
            }

            $user->forceFill(['pin_hash' => $pin, 'activado_at' => now()])->save();

            return $user;
        });
    }
}
