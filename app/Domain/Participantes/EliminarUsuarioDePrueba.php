<?php

namespace App\Domain\Participantes;

use App\Domain\Ledger\Reconciliador;
use App\Enums\Rol;
use App\Models\Asistencia;
use App\Models\Contacto;
use App\Models\Entrega;
use App\Models\Patrulla;
use App\Models\Penalizacion;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * EXCEPCIÓN a la regla de «los movimientos nunca se borran» (CLAUDE.md §2.1), solo para cuentas de PRUEBA que quedaron en
 * producción y ensucian a un equipo real. Elimina a la persona, todo lo que cargó y los movimientos que generó, y revierte
 * lo que esos movimientos acreditaron a otros (los puntos de la patrulla y los LisCoins repartidos a sus compañeros).
 *
 * Se niega si la cuenta no es de un participante, si tiene actividad de tienda o si algún compañero ya gastó los LisCoins
 * que habría que quitarle (su saldo quedaría negativo). Todo ocurre en una sola transacción y termina verificando que los saldos
 * cacheados coincidan con los movimientos; si no coinciden, se deshace todo.
 */
class EliminarUsuarioDePrueba
{
    /**
     * Qué se borraría y a quién se le corregiría el saldo. No cambia nada.
     *
     * @return array{
     *     persona: array{id: int, ci: string, nombre: string, patrulla: string|null, puntos: int, liscoins: int},
     *     registros: array<string, int>,
     *     movimientos: array{puntos_individuales: int, liscoins: int, puntos_patrulla: int},
     *     correcciones: list<array{tipo: string, id: int, nombre: string, puntos: int, liscoins: int}>,
     *     conflictos: list<string>,
     *     origenes: array<string, list<int>>,
     *     ids: array{puntos: list<int>, jlis: list<int>, patrulla: list<int>}
     * }
     *
     * @throws EliminacionNoPermitida
     */
    public function plan(User $user): array
    {
        $this->verificarPosible($user);

        $origenes = [
            (new Entrega)->getMorphClass() => Entrega::query()->where('user_id', $user->getKey())->pluck('id')->all(),
            (new Asistencia)->getMorphClass() => Asistencia::query()->where('user_id', $user->getKey())->pluck('id')->all(),
            (new Contacto)->getMorphClass() => Contacto::query()->where('user_id', $user->getKey())->pluck('id')->all(),
            (new Penalizacion)->getMorphClass() => Penalizacion::query()->where('user_id', $user->getKey())->pluck('id')->all(),
        ];

        $porOrigen = function (Builder $q) use ($origenes) {
            foreach ($origenes as $tipo => $ids) {
                if ($ids !== []) {
                    $q->orWhere(fn (Builder $w) => $w->where('origen_type', $tipo)->whereIn('origen_id', $ids));
                }
            }
        };

        $propiosOGenerados = fn (string $tabla) => DB::table($tabla)->where(fn (Builder $q) => $q->where('user_id', $user->getKey())->orWhere($porOrigen))->get(['id', 'user_id', 'cantidad']);
        $puntos = $propiosOGenerados('mov_puntos_individuales');
        $jlis = $propiosOGenerados('mov_jlis');
        $patrulla = DB::table('mov_puntos_patrulla')->where($porOrigen)->get(['id', 'patrulla_id', 'cantidad']);

        $deltaPuntos = $puntos->where('user_id', '!=', $user->getKey())->groupBy('user_id')->map->sum('cantidad');
        $deltaJlis = $jlis->where('user_id', '!=', $user->getKey())->groupBy('user_id')->map->sum('cantidad');
        $deltaPatrulla = $patrulla->groupBy('patrulla_id')->map->sum('cantidad');

        $correcciones = [];
        $conflictos = [];

        foreach (User::query()->whereIn('id', $deltaPuntos->keys()->merge($deltaJlis->keys())->unique())->orderBy('id')->get() as $otro) {
            $dp = (int) ($deltaPuntos[$otro->id] ?? 0);
            $dj = (int) ($deltaJlis[$otro->id] ?? 0);
            $correcciones[] = ['tipo' => 'persona', 'id' => $otro->id, 'nombre' => $otro->name, 'puntos' => -$dp, 'liscoins' => -$dj];

            if ($otro->jlis - $dj < 0) {
                $conflictos[] = "{$otro->name} (CI {$otro->ci}) tiene {$otro->jlis} LisCoins y habría que quitarle {$dj}: ya gastó parte. Resolvelo a mano antes.";
            }
        }

        foreach (Patrulla::query()->whereIn('id', $deltaPatrulla->keys())->orderBy('id')->get() as $p) {
            $correcciones[] = ['tipo' => 'patrulla', 'id' => $p->id, 'nombre' => $p->nombre, 'puntos' => -(int) $deltaPatrulla[$p->id], 'liscoins' => 0];
        }

        return [
            'persona' => ['id' => $user->id, 'ci' => $user->ci, 'nombre' => $user->name, 'patrulla' => $user->patrulla?->nombre, 'puntos' => $user->puntos, 'liscoins' => $user->jlis],
            'registros' => [
                'entregas' => count($origenes[(new Entrega)->getMorphClass()]),
                'asistencias' => count($origenes[(new Asistencia)->getMorphClass()]),
                'contactos' => count($origenes[(new Contacto)->getMorphClass()]),
                'penalizaciones' => count($origenes[(new Penalizacion)->getMorphClass()]),
                'reservas' => DB::table('reservas')->where('user_id', $user->getKey())->count(),
                'sesiones_abiertas' => DB::table('sessions')->where('user_id', $user->getKey())->count(),
            ],
            'movimientos' => ['puntos_individuales' => $puntos->count(), 'liscoins' => $jlis->count(), 'puntos_patrulla' => $patrulla->count()],
            'correcciones' => $correcciones,
            'conflictos' => $conflictos,
            'origenes' => $origenes,
            'ids' => ['puntos' => $puntos->pluck('id')->all(), 'jlis' => $jlis->pluck('id')->all(), 'patrulla' => $patrulla->pluck('id')->all()],
        ];
    }

    /**
     * Elimina a la persona y revierte lo que acreditó a otros. Devuelve el mismo resumen que `plan()`.
     *
     * @throws EliminacionNoPermitida
     */
    public function __invoke(User $user): array
    {
        $archivos = [];

        $resumen = DB::transaction(function () use ($user, &$archivos) {
            // Se bloquea a todos los afectados, siempre en el mismo orden, y recién ahí se toma el plan definitivo.
            $previo = $this->plan($user);
            User::query()->whereIn('id', collect($previo['correcciones'])->where('tipo', 'persona')->pluck('id')->push($user->getKey()))->orderBy('id')->lockForUpdate()->get();
            Patrulla::query()->whereIn('id', collect($previo['correcciones'])->where('tipo', 'patrulla')->pluck('id'))->orderBy('id')->lockForUpdate()->get();

            $plan = $this->plan($user->fresh());

            if ($plan['conflictos'] !== []) {
                throw new EliminacionNoPermitida(implode("\n", $plan['conflictos']));
            }

            foreach ($plan['correcciones'] as $c) {
                $c['tipo'] === 'persona'
                    ? User::query()->whereKey($c['id'])->update(['puntos' => DB::raw('puntos + '.(int) $c['puntos']), 'jlis' => DB::raw('jlis + '.(int) $c['liscoins'])])
                    : Patrulla::query()->whereKey($c['id'])->update(['puntos' => DB::raw('puntos + '.(int) $c['puntos'])]);
            }

            // Query builder a propósito: los modelos de movimientos impiden borrar, y esta es la única excepción.
            foreach ([['mov_puntos_individuales', 'puntos'], ['mov_jlis', 'jlis'], ['mov_puntos_patrulla', 'patrulla']] as [$tabla, $clave]) {
                foreach (array_chunk($plan['ids'][$clave], 500) as $ids) {
                    DB::table($tabla)->whereIn('id', $ids)->delete();
                }
            }

            $entregas = $plan['origenes'][(new Entrega)->getMorphClass()];
            $archivos = array_merge(
                DB::table('entrega_imagenes')->whereIn('entrega_id', $entregas)->pluck('ruta')->all(),
                DB::table('contactos')->where('user_id', $user->getKey())->whereNotNull('captura_ruta')->pluck('captura_ruta')->all(),
            );

            DB::table('entrega_imagenes')->whereIn('entrega_id', $entregas)->delete();
            DB::table('entregas')->where('user_id', $user->getKey())->delete();
            DB::table('contactos')->where('user_id', $user->getKey())->delete();
            DB::table('asistencias')->where('user_id', $user->getKey())->delete();
            DB::table('reservas')->where('user_id', $user->getKey())->delete();
            DB::table('penalizaciones')->where('user_id', $user->getKey())->delete();
            DB::table('sessions')->where('user_id', $user->getKey())->delete();
            DB::table('users')->where('id', $user->getKey())->delete();

            $diferencias = (new Reconciliador)->diferencias();

            if ($diferencias !== []) {
                throw new EliminacionNoPermitida('Los saldos no coinciden con los movimientos después de eliminar ('.count($diferencias).' diferencias). No se cambió nada.');
            }

            return $plan;
        });

        // Los archivos se borran recién con la transacción confirmada.
        Storage::disk('private')->delete($archivos);

        return $resumen;
    }

    /** @throws EliminacionNoPermitida */
    private function verificarPosible(User $user): void
    {
        if ($user->rol !== Rol::Beneficiario) {
            throw new EliminacionNoPermitida('Solo se pueden eliminar cuentas de participantes.');
        }

        $id = $user->getKey();
        $bloqueos = [
            'cobros de la tienda' => DB::table('cobros')->where(fn ($q) => $q->where('creado_por', $id)->orWhere('pagado_por', $id))->count(),
            'devoluciones' => DB::table('devoluciones')->where(fn ($q) => $q->where('user_id', $id)->orWhere('registrado_por', $id))->count(),
            'revisiones o registros hechos como responsable' => DB::table('entregas')->where('revisado_por', $id)->count()
                + DB::table('contactos')->where('revisado_por', $id)->count()
                + DB::table('penalizaciones')->where(fn ($q) => $q->where('registrado_por', $id)->orWhere('anulada_por', $id))->count()
                + DB::table('mov_puntos_individuales')->where('registrado_por', $id)->count()
                + DB::table('mov_jlis')->where('registrado_por', $id)->count()
                + DB::table('mov_puntos_patrulla')->where('registrado_por', $id)->count()
                + DB::table('sesiones_asistencia')->where('abierta_por', $id)->count(),
        ];

        foreach ($bloqueos as $que => $cuantos) {
            if ($cuantos > 0) {
                throw new EliminacionNoPermitida("La cuenta tiene {$cuantos} {$que}: no se puede eliminar sin tocar registros de otras personas.");
            }
        }
    }
}
