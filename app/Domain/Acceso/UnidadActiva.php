<?php

namespace App\Domain\Acceso;

use App\Enums\Rol;
use App\Models\Unidad;
use App\Models\User;

/**
 * Unidad sobre la que actúan las pantallas de dirigente. El dirigente trabaja siempre sobre la suya; el
 * administrador elige una (queda en su sesión) y, si no eligió, se usa la primera.
 */
class UnidadActiva
{
    private const CLAVE = 'unidad_activa';

    public function para(User $user): ?int
    {
        if ($user->rol !== Rol::Admin) {
            return $user->unidad_id;
        }

        $elegida = session(self::CLAVE);

        if ($elegida && Unidad::query()->whereKey($elegida)->exists()) {
            return (int) $elegida;
        }

        return Unidad::query()->orderBy('id')->value('id');
    }

    /** Solo el administrador puede cambiar de unidad. */
    public function elegir(User $user, int $unidadId): void
    {
        abort_unless($user->rol === Rol::Admin, 403);

        Unidad::query()->findOrFail($unidadId);
        session([self::CLAVE => $unidadId]);
    }

    /** @return array<int, string> id => nombre, para el selector del administrador */
    public function opciones(): array
    {
        return Unidad::query()->orderBy('id')->pluck('nombre', 'id')->all();
    }
}
