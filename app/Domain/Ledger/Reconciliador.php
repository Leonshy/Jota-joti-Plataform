<?php

namespace App\Domain\Ledger;

use App\Models\Patrulla;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula los saldos desde los movimientos (fuente de verdad) y los compara con las
 * columnas cacheadas.
 */
class Reconciliador
{
    /**
     * @return list<array{tipo: string, id: int, esperado: int, cacheado: int}>
     */
    public function diferencias(): array
    {
        return [
            ...$this->comparar('puntos', User::class, 'mov_puntos_individuales', 'user_id', 'puntos'),
            ...$this->comparar('jlis', User::class, 'mov_jlis', 'user_id', 'jlis'),
            ...$this->comparar('puntos_patrulla', Patrulla::class, 'mov_puntos_patrulla', 'patrulla_id', 'puntos'),
        ];
    }

    /** Reescribe las columnas cacheadas con el saldo calculado. Devuelve cuántas corrigió. */
    public function corregir(): int
    {
        $diferencias = $this->diferencias();

        DB::transaction(function () use ($diferencias) {
            foreach ($diferencias as $d) {
                $modelo = $d['tipo'] === 'puntos_patrulla' ? Patrulla::class : User::class;
                $columna = $d['tipo'] === 'jlis' ? 'jlis' : 'puntos';
                $modelo::query()->whereKey($d['id'])->update([$columna => $d['esperado']]);
            }
        });

        return count($diferencias);
    }

    private function comparar(string $tipo, string $modelo, string $tabla, string $fk, string $columna): array
    {
        $sumas = DB::table($tabla)->selectRaw("{$fk} as id, SUM(cantidad) as total")->groupBy($fk)->pluck('total', 'id');
        $diferencias = [];

        foreach ($modelo::query()->select(['id', $columna])->cursor() as $fila) {
            $esperado = (int) ($sumas[$fila->id] ?? 0);
            $cacheado = (int) $fila->{$columna};

            if ($esperado !== $cacheado) {
                $diferencias[] = ['tipo' => $tipo, 'id' => $fila->id, 'esperado' => $esperado, 'cacheado' => $cacheado];
            }
        }

        return $diferencias;
    }
}
