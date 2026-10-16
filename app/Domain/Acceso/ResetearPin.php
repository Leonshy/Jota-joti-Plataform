<?php

namespace App\Domain\Acceso;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Reseteo por admin: borra el PIN y cierra las sesiones. La persona vuelve a crear su PIN
 * ingresando solo su CI.
 */
class ResetearPin
{
    public function __invoke(User $admin, User $objetivo): void
    {
        Gate::forUser($admin)->authorize('resetearPin', $objetivo);

        DB::transaction(function () use ($objetivo) {
            $objetivo->forceFill([
                'pin_hash' => null,
                'activado_at' => null,
                'remember_token' => null,
            ])->save();

            DB::table('sessions')->where('user_id', $objetivo->getKey())->delete();
        });

        activity('acceso')->causedBy($admin)->performedOn($objetivo)->log('Reseteó el PIN');
    }
}
