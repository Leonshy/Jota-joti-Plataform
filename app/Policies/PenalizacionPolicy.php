<?php

namespace App\Policies;

use App\Models\Penalizacion;
use App\Models\User;

class PenalizacionPolicy
{
    /** Anula quien puede gestionar la unidad de la persona o patrulla penalizada (CLAUDE.md §2.7). */
    public function anular(User $user, Penalizacion $penalizacion): bool
    {
        return $user->puedeGestionarUnidad($penalizacion->unidadId());
    }
}
