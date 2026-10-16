<?php

namespace App\Domain\Acceso;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Valida CI + PIN. Sin bloqueo ni límite de intentos: prioriza que entren rápido y fácil.
 * No inicia la sesión: eso lo hace el componente que llama.
 */
class LoginCiPin
{
    public const AVISO_PARTICIPANTE = 40;

    public const AVISO_CUENTA_CON_PODER = 10;

    /**
     * @throws CiNoRegistrado
     * @throws CuentaSinActivar
     * @throws PinIncorrecto
     */
    public function __invoke(string $ci, string $pin): User
    {
        $user = User::query()->where('ci', trim($ci))->first();

        if (! $user) {
            throw new CiNoRegistrado;
        }

        if (! $user->activado_at) {
            throw new CuentaSinActivar;
        }

        if (! Hash::check($pin, $user->pin_hash)) {
            $this->vigilarFallos($user);

            throw new PinIncorrecto;
        }

        return $user;
    }

    /**
     * Detección SIN bloqueo (decisión de producto: no hay límite de intentos). Si una cuenta acumula muchos PIN
     * incorrectos en poco tiempo se deja un aviso en el registro para que el responsable técnico lo vea; la persona
     * sigue pudiendo intentar. Las cuentas con más poder (dirigente, tienda, admin) avisan antes.
     */
    private function vigilarFallos(User $user): void
    {
        $clave = 'pin-fallos:'.$user->getKey();
        Cache::add($clave, 0, now()->addMinutes(15));
        $fallos = Cache::increment($clave);
        $umbral = $user->rol === Rol::Beneficiario ? self::AVISO_PARTICIPANTE : self::AVISO_CUENTA_CON_PODER;

        if ($fallos === $umbral) {
            Log::warning('Posible adivinación de PIN: muchos intentos fallidos en 15 minutos.', ['user_id' => $user->getKey(), 'rol' => $user->rol->value, 'fallos' => $fallos]);
        }
    }
}
