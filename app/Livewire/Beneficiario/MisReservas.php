<?php

namespace App\Livewire\Beneficiario;

use App\Domain\Actividades\AgendaDelParticipante;
use App\Domain\Actividades\CancelarReserva;
use App\Domain\Actividades\TurnoYaComenzo;
use App\Models\Reserva;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.movil')]
class MisReservas extends Component
{
    /** Reserva que se está por cancelar (pide confirmación antes). */
    public ?int $confirmar = null;

    public string $mensaje = '';

    public function pedirCancelar(int $id): void
    {
        $this->confirmar = $id;
        $this->mensaje = '';
    }

    public function noCancelar(): void
    {
        $this->confirmar = null;
    }

    public function cancelar(CancelarReserva $cancelar): void
    {
        $reserva = Reserva::query()->find($this->confirmar);
        $this->confirmar = null;

        if (! $reserva) {
            return; // ya estaba cancelada
        }

        try {
            $cancelar(auth()->user(), $reserva);
            $this->mensaje = '';
        } catch (TurnoYaComenzo) {
            $this->mensaje = __('jota.actividades.ya_empezo');
        } catch (AuthorizationException) {
            abort(403);
        }
    }

    public function render(AgendaDelParticipante $agenda)
    {
        return view('livewire.beneficiario.mis-reservas', [
            'reservas' => $agenda->reservas(auth()->user()),
        ])->title(__('jota.actividades.mis_reservas'));
    }
}
