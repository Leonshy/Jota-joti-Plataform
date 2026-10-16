<?php

namespace App\Policies;

use App\Models\Patrulla;
use App\Models\User;

class PatrullaPolicy
{
    public function gestionar(User $user, Patrulla $patrulla): bool
    {
        return $user->puedeGestionarUnidad($patrulla->unidad_id);
    }

    public function penalizar(User $user, Patrulla $patrulla): bool
    {
        return $this->gestionar($user, $patrulla);
    }
}
