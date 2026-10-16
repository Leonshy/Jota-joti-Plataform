<?php

namespace App\Domain\Misiones;

use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use App\Models\Mision;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Lo que el participante ve de sus misiones, siempre a partir de la hora del servidor.
 * Estado de una misión para quien mira: aprobada > pendiente > cerrada/vencida > rechazada > nueva.
 */
class MisionesDelParticipante
{
    /**
     * @return list<array{id: int, titulo: string, por_patrulla: bool, puntos: int, estado: string, vence_at: CarbonInterface|null}>
     */
    public function lista(User $user): array
    {
        $misiones = Mision::query()->visiblesPara($user)->get();
        $entregas = $this->entregasDe($user, $misiones->modelKeys())->groupBy('mision_id');

        return $misiones
            ->map(fn (Mision $m) => [
                'id' => $m->getKey(),
                'titulo' => $m->titulo,
                'por_patrulla' => $m->esPorPatrulla(),
                'puntos' => $m->puntos,
                'estado' => $this->estado($m, $entregas->get($m->getKey(), collect())),
                'vence_at' => $m->vence_at,
                'lanza_at' => $m->lanza_at,
            ])
            // Primero lo que se puede hacer, después lo que espera, al final lo que ya terminó.
            ->sortBy([
                fn ($a, $b) => $this->orden($a['estado']) <=> $this->orden($b['estado']),
                fn ($a, $b) => $b['lanza_at'] <=> $a['lanza_at'],
            ])
            ->values()
            ->all();
    }

    /**
     * Detalle de una misión: su estado y la entrega que cuenta (la aprobada o la pendiente; si no hay, la última rechazada).
     *
     * @return array{estado: string, entrega: array<string, mixed>|null, puede_entregar: bool, abierta: bool}
     */
    public function detalle(User $user, Mision $mision): array
    {
        $entregas = $this->entregasDe($user, [$mision->getKey()])->load('user:id,name', 'imagenes');
        $estado = $this->estado($mision, $entregas);

        $relevante = match ($estado) {
            'aprobada' => $entregas->firstWhere('estado', EstadoEntrega::Aprobada),
            'pendiente' => $entregas->firstWhere('estado', EstadoEntrega::Pendiente),
            default => $entregas->sortByDesc('id')->first(),
        };

        return [
            'estado' => $estado,
            'abierta' => $mision->estaAbierta(),
            'puede_entregar' => in_array($estado, ['nueva', 'rechazada'], true) && ! ($mision->esPorPatrulla() && ! $user->patrulla_id),
            'entrega' => $relevante ? [
                'texto' => $relevante->texto,
                'motivo_rechazo' => $relevante->motivo_rechazo,
                // En una misión por patrulla puede haberla entregado otro integrante.
                'entregada_por' => $relevante->user_id === $user->getKey() ? null : strtok($relevante->user->name, ' '),
                'imagenes' => $relevante->imagenes->map(fn ($i) => [
                    'url' => $i->procesada_at ? route('entregas.imagen', $i) : null,
                ])->all(),
            ] : null,
        ];
    }

    /** @param  Collection<int, Entrega>  $entregas */
    private function estado(Mision $mision, Collection $entregas): string
    {
        return match (true) {
            $entregas->contains('estado', EstadoEntrega::Aprobada) => 'aprobada',
            $entregas->contains('estado', EstadoEntrega::Pendiente) => 'pendiente',
            $mision->cerrada_at !== null => 'cerrada',
            ! $mision->estaAbierta() => 'vencida',
            $entregas->contains('estado', EstadoEntrega::Rechazada) => 'rechazada',
            default => 'nueva',
        };
    }

    private function orden(string $estado): int
    {
        return ['nueva' => 0, 'rechazada' => 0, 'pendiente' => 1, 'aprobada' => 2, 'vencida' => 3, 'cerrada' => 3][$estado];
    }

    /**
     * Entregas que cuentan para esta persona: las suyas y las de su patrulla (misiones por patrulla).
     *
     * @param  list<int>  $misionIds
     * @return Collection<int, Entrega>
     */
    private function entregasDe(User $user, array $misionIds): Collection
    {
        return Entrega::query()
            ->whereIn('mision_id', $misionIds)
            ->where(fn (Builder $q) => $q
                ->where('user_id', $user->getKey())
                ->when($user->patrulla_id, fn (Builder $p) => $p->orWhere('patrulla_id', $user->patrulla_id)))
            ->get();
    }
}
