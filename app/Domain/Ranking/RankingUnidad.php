<?php

namespace App\Domain\Ranking;

use App\Enums\ModoRanking;
use App\Enums\Rol;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Rankings por unidad (D7: se mezclan los dos grupos scouts), con caché de 30 s para no
 * recalcular en cada polling (§5.4.9).
 *
 *  - Tropa y Caminantes (equipo): patrullas.puntos + SUM(users.puntos de sus integrantes).
 *  - Rovers (individual): users.puntos de los beneficiarios de la unidad.
 *
 * Empates comparten posición (1, 1, 3).
 *
 * REGLA DE ORO (CLAUDE.md §2.8): la vista del beneficiario y la del dirigente exponen SOLO
 * `posicion` y `puntaje`. Nunca nombres, ids, ni nada que marque la posición propia. La caché
 * pública se arma aparte de la del admin y nunca contiene identidades.
 */
class RankingUnidad
{
    public const TTL = 30;

    /** Tabla anónima [{posicion, puntaje}] de una unidad, cacheada. */
    public function publico(Unidad $unidad): array
    {
        return Cache::remember("ranking:publico:{$unidad->getKey()}", self::TTL, function () use ($unidad) {
            $filas = $this->calcular($unidad);

            return array_map(
                fn (array $f) => ['posicion' => $f['posicion'], 'puntaje' => $f['puntaje']],
                $filas,
            );
        });
    }

    /**
     * Lo que ve un beneficiario: la tabla anónima de su unidad y, aparte, su puntaje individual y
     * el de su patrulla. Nada une esos números con una fila de la tabla.
     *
     * @return array{modo: string, tabla: list<array{posicion: int, puntaje: int}>, mis_puntos: int, puntos_patrulla: int|null, se_actualiza_cada: int}
     */
    public function vistaBeneficiario(User $user): array
    {
        $unidad = $this->unidadVisible($user, [Rol::Beneficiario]);

        $puntosPatrulla = $unidad->modo_ranking === ModoRanking::Equipo && $user->patrulla_id
            ? (int) DB::table('patrullas')->where('id', $user->patrulla_id)->value('puntos')
            : null;

        return [
            'modo' => $unidad->modo_ranking->value,
            'tabla' => $this->publico($unidad),
            'mis_puntos' => (int) DB::table('users')->where('id', $user->getKey())->value('puntos'),
            'puntos_patrulla' => $puntosPatrulla,
            'se_actualiza_cada' => self::TTL,
        ];
    }

    /**
     * El dirigente ve la tabla de su unidad sin nombres (D6).
     *
     * @return array{modo: string, tabla: list<array{posicion: int, puntaje: int}>, se_actualiza_cada: int}
     */
    public function vistaDirigente(User $user, ?int $unidadId = null): array
    {
        // El administrador mira la unidad que eligió (misma tabla anónima); el dirigente, siempre la suya.
        if ($user->rol === Rol::Admin && $unidadId) {
            $unidad = Unidad::query()->findOrFail($unidadId);
            Gate::forUser($user)->authorize('verRankingCompleto', $unidad);
        } else {
            $unidad = $this->unidadVisible($user, [Rol::Dirigente]);
        }

        return [
            'modo' => $unidad->modo_ranking->value,
            'tabla' => $this->publico($unidad),
            'se_actualiza_cada' => self::TTL,
        ];
    }

    /**
     * Ranking completo con nombres y desglose, solo para el administrador.
     *
     * @return list<array<string, mixed>>
     */
    public function completo(User $admin, Unidad $unidad): array
    {
        Gate::forUser($admin)->authorize('verRankingCompleto', $unidad);

        return Cache::remember("ranking:admin:{$unidad->getKey()}", self::TTL, fn () => $this->calcular($unidad));
    }

    /** @return array<string, list<array<string, mixed>>> nombre de unidad → ranking completo */
    public function todas(User $admin): array
    {
        $resultado = [];

        foreach (Unidad::query()->orderBy('id')->get() as $unidad) {
            $resultado[$unidad->nombre] = $this->completo($admin, $unidad);
        }

        return $resultado;
    }

    /** Filas con identidad, ordenadas y con posición. Uso interno: NUNCA se devuelve tal cual al beneficiario. */
    private function calcular(Unidad $unidad): array
    {
        $filas = $unidad->modo_ranking === ModoRanking::Equipo
            ? $this->porEquipo($unidad)
            : $this->individual($unidad);

        return $this->conPosiciones($filas);
    }

    private function porEquipo(Unidad $unidad): array
    {
        return DB::table('patrullas as p')
            ->leftJoin('users as u', 'u.patrulla_id', '=', 'p.id')
            ->join('grupos as g', 'g.id', '=', 'p.grupo_id')
            ->where('p.unidad_id', $unidad->getKey())
            ->groupBy('p.id', 'p.nombre', 'p.puntos', 'g.nombre')
            ->selectRaw('p.id as patrulla_id, p.nombre as patrulla, g.nombre as grupo, p.puntos as puntos_patrulla,
                COALESCE(SUM(u.puntos), 0) as puntos_individuales, COUNT(u.id) as integrantes')
            ->get()
            ->map(fn ($f) => [
                'patrulla_id' => (int) $f->patrulla_id,
                'patrulla' => $f->patrulla,
                'grupo' => $f->grupo,
                'puntos_patrulla' => (int) $f->puntos_patrulla,
                'puntos_individuales' => (int) $f->puntos_individuales,
                'integrantes' => (int) $f->integrantes,
                'puntaje' => (int) $f->puntos_patrulla + (int) $f->puntos_individuales,
                'orden' => $f->patrulla,
            ])
            ->all();
    }

    private function individual(Unidad $unidad): array
    {
        return DB::table('users as u')
            ->leftJoin('grupos as g', 'g.id', '=', 'u.grupo_id')
            ->where('u.unidad_id', $unidad->getKey())
            ->where('u.rol', Rol::Beneficiario->value)
            ->select('u.id as user_id', 'u.ci', 'u.name as nombre', 'g.nombre as grupo', 'u.puntos')
            ->get()
            ->map(fn ($f) => [
                'user_id' => (int) $f->user_id,
                'ci' => $f->ci,
                'nombre' => $f->nombre,
                'grupo' => $f->grupo,
                'puntaje' => (int) $f->puntos,
                'orden' => $f->nombre,
            ])
            ->all();
    }

    /** Orden por puntaje descendente (desempate por nombre) y posición de competencia: 1, 1, 3. */
    private function conPosiciones(array $filas): array
    {
        usort($filas, fn ($a, $b) => [$b['puntaje'], $a['orden']] <=> [$a['puntaje'], $b['orden']]);

        $posicion = 0;
        $anterior = null;

        foreach ($filas as $i => &$fila) {
            if ($fila['puntaje'] !== $anterior) {
                $posicion = $i + 1;
                $anterior = $fila['puntaje'];
            }

            $fila = ['posicion' => $posicion] + array_diff_key($fila, ['orden' => true]);
        }

        return $filas;
    }

    private function unidadVisible(User $user, array $roles): Unidad
    {
        if (! in_array($user->rol, $roles, true) || $user->unidad_id === null) {
            throw new AuthorizationException;
        }

        $unidad = Unidad::query()->findOrFail($user->unidad_id);

        Gate::forUser($user)->authorize('verRanking', $unidad);

        return $unidad;
    }
}
