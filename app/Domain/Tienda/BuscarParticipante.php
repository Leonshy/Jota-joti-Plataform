<?php

namespace App\Domain\Tienda;

use App\Enums\Rol;
use App\Models\Cobro;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

/** Búsqueda de participantes por CI exacto o por nombre, para las devoluciones. */
class BuscarParticipante
{
    public const MIN_NOMBRE = 3;

    public const MAX_RESULTADOS = 10;

    /** @return Collection<int, User> */
    public function __invoke(User $buscador, string $consulta): Collection
    {
        Gate::forUser($buscador)->authorize('buscar', Cobro::class);

        $consulta = trim($consulta);

        if ($consulta === '') {
            return new Collection;
        }

        $esCi = preg_match('/^\d+$/', $consulta) === 1;

        if (! $esCi && mb_strlen($consulta) < self::MIN_NOMBRE) {
            return new Collection;
        }

        return User::query()
            ->select(['id', 'ci', 'name', 'unidad_id', 'patrulla_id'])
            ->where('rol', Rol::Beneficiario)
            ->where(function ($q) use ($consulta, $esCi) {
                $q->where('ci', $consulta);

                if (! $esCi) {
                    $q->orWhere('name', 'like', '%'.addcslashes($consulta, '%_\\').'%');
                }
            })
            ->orderBy('name')
            ->limit(self::MAX_RESULTADOS)
            ->get();
    }
}
