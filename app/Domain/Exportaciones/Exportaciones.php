<?php

namespace App\Domain\Exportaciones;

use App\Domain\Ranking\RankingUnidad;
use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Datos para la premiación (§4.9): rankings completos y saldos de todos los participantes. */
class Exportaciones
{
    public const ENCABEZADOS_RANKING = ['Unidad', 'Modo', 'Posición', 'Nombre', 'CI', 'Grupo', 'Puntaje', 'Puntos de patrulla', 'Puntos individuales', 'Integrantes'];

    public const ENCABEZADOS_SALDOS = ['CI', 'Nombre', 'Grupo', 'Unidad', 'Patrulla', 'Puntos', 'LisCoins', 'Puntos de la patrulla'];

    public function __construct(private RankingUnidad $ranking) {}

    /** @return list<list<mixed>> */
    public function rankings(User $admin): array
    {
        Gate::forUser($admin)->authorize('exportar', User::class);

        $filas = [];

        foreach ($this->ranking->todas($admin) as $unidad => $ranking) {
            foreach ($ranking as $f) {
                $filas[] = isset($f['patrulla'])
                    ? [$unidad, 'Equipo', $f['posicion'], $f['patrulla'], '', $f['grupo'], $f['puntaje'], $f['puntos_patrulla'], $f['puntos_individuales'], $f['integrantes']]
                    : [$unidad, 'Individual', $f['posicion'], $f['nombre'], $f['ci'], $f['grupo'] ?? '', $f['puntaje'], '', '', ''];
            }
        }

        return $filas;
    }

    /** @return list<list<mixed>> */
    public function saldos(User $admin): array
    {
        Gate::forUser($admin)->authorize('exportar', User::class);

        return DB::table('users as u')
            ->leftJoin('grupos as g', 'g.id', '=', 'u.grupo_id')
            ->leftJoin('unidades as un', 'un.id', '=', 'u.unidad_id')
            ->leftJoin('patrullas as p', 'p.id', '=', 'u.patrulla_id')
            ->where('u.rol', Rol::Beneficiario->value)
            ->orderBy('un.nombre')->orderBy('p.nombre')->orderBy('u.name')
            ->select('u.ci', 'u.name', 'g.nombre as grupo', 'un.nombre as unidad', 'p.nombre as patrulla', 'u.puntos', 'u.jlis', 'p.puntos as puntos_patrulla')
            ->get()
            ->map(fn ($f) => [$f->ci, $f->name, $f->grupo ?? '', $f->unidad ?? '', $f->patrulla ?? '', (int) $f->puntos, (int) $f->jlis, $f->puntos_patrulla === null ? '' : (int) $f->puntos_patrulla])
            ->all();
    }
}
