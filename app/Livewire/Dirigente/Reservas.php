<?php

namespace App\Livewire\Dirigente;

use App\Domain\Actividades\CancelarReserva;
use App\Domain\Actividades\ReservasDeUnidad;
use App\Models\Reserva;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Reservas vigentes de la unidad; el dirigente puede cancelar cualquiera (confirmando antes). */
#[Layout('components.layouts.movil')]
class Reservas extends Component
{
    public string $buscar = '';

    public ?int $confirmar = null;

    public function pedirCancelar(int $id): void
    {
        $this->confirmar = $id;
    }

    public function noCancelar(): void
    {
        $this->confirmar = null;
    }

    public function cancelar(CancelarReserva $cancelar, ReservasDeUnidad $reservas): void
    {
        $id = $this->confirmar;
        $this->confirmar = null;

        // Solo se puede confirmar una reserva que está en la lista de este dirigente.
        $reserva = $reservas(auth()->user(), null, unidad_activa())->where('reservas.id', $id)->first();

        if (! $reserva) {
            return;
        }

        try {
            $cancelar(auth()->user(), Reserva::query()->findOrFail($reserva->getKey()));
        } catch (AuthorizationException) {
            abort(403);
        }
    }

    public function render(ReservasDeUnidad $reservas)
    {
        $filas = $reservas(auth()->user(), $this->buscar, unidad_activa())->limit(300)->get()->map(fn (Reserva $r) => [
            'id' => $r->getKey(),
            'turno' => $r->turno_id,
            'actividad' => $r->turno->actividad->titulo,
            'inicio' => $r->turno->inicio,
            'fin' => $r->turno->fin,
            'quien' => match (true) {
                $r->unidad_id !== null => __('jota.dirigente.toda_la_unidad', ['nombre' => $r->unidad->nombre]),
                $r->patrulla_id !== null => __('jota.inicio.tu_patrulla', ['nombre' => $r->patrulla->nombre]),
                default => $r->user->name,
            },
            'detalle' => $r->user_id ? $r->user->patrulla?->nombre : null,
            'comenzado' => $r->turno->haComenzado(),
        ])->groupBy('turno')->values()->all();

        return view('livewire.dirigente.reservas', ['grupos' => $filas])->title(__('jota.dirigente.reservas_titulo'));
    }
}
