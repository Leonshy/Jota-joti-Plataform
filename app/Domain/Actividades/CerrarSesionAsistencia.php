<?php

namespace App\Domain\Actividades;

use App\Models\SesionAsistencia;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/** Cerrar invalida el enlace público y el QR. Cerrar una sesión ya cerrada no hace nada. */
class CerrarSesionAsistencia
{
    public function __invoke(User $dirigente, SesionAsistencia $sesion): SesionAsistencia
    {
        Gate::forUser($dirigente)->authorize('gestionar', $sesion->actividad);

        SesionAsistencia::query()->whereKey($sesion->getKey())->whereNull('cerrada_at')->update(['cerrada_at' => now()]);

        return $sesion->refresh();
    }
}
