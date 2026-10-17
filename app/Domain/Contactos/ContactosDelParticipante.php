<?php

namespace App\Domain\Contactos;

use App\Domain\Actividades\EquipoDeReserva;
use App\Enums\EstadoEntrega;
use App\Enums\TipoContacto;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lo que ve el participante: sus contactos JOTI y los de su equipo en JOTA (los carga cualquier integrante de la patrulla; en los
 * Rovers, de toda la unidad). Solo lectura.
 */
class ContactosDelParticipante
{
    /** @return Builder<Contacto> */
    private function propios(User $user): Builder
    {
        return Contacto::query()->where(function (Builder $q) use ($user) {
            $q->where(fn (Builder $j) => $j->where('tipo', TipoContacto::Joti)->where('user_id', $user->getKey()));

            if ($user->patrulla_id) {
                $q->orWhere(fn (Builder $j) => $j->where('tipo', TipoContacto::Jota)->where('patrulla_id', $user->patrulla_id));
            } elseif (EquipoDeReserva::unidadEsEquipo($user->unidad_id)) {
                $q->orWhere(fn (Builder $j) => $j->where('tipo', TipoContacto::Jota)->whereNull('patrulla_id')->where('unidad_id', $user->unidad_id));
            }
        });
    }

    /** @return list<Contacto> los más nuevos primero */
    public function lista(User $user, TipoContacto $tipo, int $limite = 200): array
    {
        return $this->propios($user)->where('tipo', $tipo)->with('user:id,name')->orderByDesc('id')->limit($limite)->get()->all();
    }

    /**
     * Cuántos contactos tiene de cada tipo, en una sola consulta.
     *
     * @return array{jota: array{aprobados: int, pendientes: int}, joti: array{aprobados: int, pendientes: int}}
     */
    public function resumen(User $user): array
    {
        $filas = $this->propios($user)->selectRaw('tipo, estado, count(*) as total')->groupBy('tipo', 'estado')->get();
        $cuenta = fn (TipoContacto $t, EstadoEntrega $e) => (int) $filas->first(fn ($f) => $f->tipo === $t && $f->estado === $e)?->total;

        return [
            'jota' => ['aprobados' => $cuenta(TipoContacto::Jota, EstadoEntrega::Aprobada), 'pendientes' => $cuenta(TipoContacto::Jota, EstadoEntrega::Pendiente)],
            'joti' => ['aprobados' => $cuenta(TipoContacto::Joti, EstadoEntrega::Aprobada), 'pendientes' => $cuenta(TipoContacto::Joti, EstadoEntrega::Pendiente)],
        ];
    }
}
