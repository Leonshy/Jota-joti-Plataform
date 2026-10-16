<?php

namespace App\Domain\Inicio;

use App\Models\Asistencia;
use App\Models\Cobro;
use App\Models\CumplimientoEquipo;
use App\Models\Devolucion;
use App\Models\Entrega;
use App\Models\Penalizacion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Movimientos de puntos y de LisCoins de una persona, los más nuevos primero. Solo lectura sobre el ledger
 * de solo agregar; pide una fila de más para saber si hay más para cargar.
 */
class HistorialDelParticipante
{
    /**
     * @param  'todo'|'puntos'|'jlis'  $filtro
     * @return array{filas: list<array{tipo: string, cantidad: int, origen: string, detalle: string|null, cuando: Carbon}>, hay_mas: bool}
     */
    public function __invoke(User $user, string $filtro = 'todo', int $limite = 20): array
    {
        $tabla = fn (string $nombre, string $tipo) => DB::table($nombre)
            ->where('user_id', $user->getKey())
            ->select(DB::raw("'{$tipo}' as tipo"), 'id', 'cantidad', 'motivo', 'origen_type', 'created_at');

        $consulta = match ($filtro) {
            'puntos' => $tabla('mov_puntos_individuales', 'puntos'),
            'jlis' => $tabla('mov_jlis', 'jlis'),
            default => $tabla('mov_puntos_individuales', 'puntos')->unionAll($tabla('mov_jlis', 'jlis')),
        };

        $filas = DB::query()->fromSub($consulta, 'm')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit($limite + 1)->get();

        return [
            'filas' => $filas->take($limite)->map(fn ($m) => [
                'tipo' => $m->tipo,
                'cantidad' => (int) $m->cantidad,
                'origen' => $this->origen($m->origen_type, (int) $m->cantidad),
                'detalle' => $m->motivo,
                'cuando' => Carbon::parse($m->created_at),
            ])->values()->all(),
            'hay_mas' => $filas->count() > $limite,
        ];
    }

    private function origen(?string $clase, int $cantidad): string
    {
        return match ($clase) {
            Entrega::class => 'mision',
            Asistencia::class => 'asistencia',
            CumplimientoEquipo::class => 'cumplimiento',
            Cobro::class => 'tienda',
            Devolucion::class => 'devolucion',
            Penalizacion::class => $cantidad > 0 ? 'anulacion' : 'penalizacion',
            default => 'ajuste',
        };
    }
}
