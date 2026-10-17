<?php

namespace App\Policies;

use App\Models\Contacto;
use App\Models\User;

class ContactoPolicy
{
    /**
     * Quien lo cargó; en JOTA también su patrulla (o, en los Rovers, su unidad), que es quien suma; y quien lo revisa.
     */
    public function ver(User $user, Contacto $contacto): bool
    {
        return $user->is($contacto->user)
            || ($contacto->esJota() && $contacto->patrulla_id !== null && $user->patrulla_id === $contacto->patrulla_id)
            || $this->revisar($user, $contacto);
    }

    /** Aprobar o rechazar: dirigente de la unidad de quien lo cargó, o admin (CLAUDE.md §2.7). */
    public function revisar(User $user, Contacto $contacto): bool
    {
        return $user->puedeGestionarUnidad($contacto->unidad_id);
    }
}
