<?php

namespace App\Domain\Misiones;

use App\Models\Mision;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CerrarMision
{
    public function __invoke(User $admin, Mision $mision): Mision
    {
        Gate::forUser($admin)->authorize('cerrar', $mision);

        $mision->forceFill(['cerrada_at' => $mision->cerrada_at ?? now()])->save();

        return $mision;
    }
}
