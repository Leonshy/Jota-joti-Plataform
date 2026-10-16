<?php

namespace App\Livewire\Acceso;

use App\Domain\Acceso\ActivarCuenta;
use App\Domain\Acceso\CiNoRegistrado;
use App\Domain\Acceso\CuentaYaActivada;
use App\Domain\Acceso\DestinoPorRol;
use App\Domain\Participantes\ReglasParticipante;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Primer ingreso: con solo el CI, la persona crea su PIN de 4 números (escrito dos veces). */
#[Layout('components.layouts.movil')]
class Activar extends Component
{
    public int $paso = 1;

    public string $ci = '';

    public string $pin = '';

    public string $pinRepetido = '';

    public function mount(): void
    {
        $this->ci = (string) request()->query('ci', '');
    }

    public function continuar(ActivarCuenta $activar): void
    {
        $this->resetErrorBag();
        $ci = ReglasParticipante::normalizarCi($this->ci);

        if ($ci === '') {
            $this->addError('ci', __('jota.acceso.error_ci_vacio'));

            return;
        }

        try {
            $activar->verificar($ci);
        } catch (CiNoRegistrado) {
            $this->addError('ci', __('jota.acceso.error_ci'));

            return;
        } catch (CuentaYaActivada) {
            $this->addError('ci', __('jota.acceso.error_ya_activada'));

            return;
        }

        $this->ci = $ci;
        $this->paso = 2;
    }

    public function crear(ActivarCuenta $activar): void
    {
        $this->resetErrorBag();

        if (! preg_match('/^\d{4}$/', $this->pin)) {
            $this->addError('pin', __('jota.acceso.error_pin_formato'));

            return;
        }

        if ($this->pin !== $this->pinRepetido) {
            $this->addError('pinRepetido', __('jota.acceso.error_pin_distinto'));

            return;
        }

        try {
            $user = $activar($this->ci, $this->pin);
        } catch (CuentaYaActivada|CiNoRegistrado) {
            // Otra persona (o ella misma en otro celular) la activó mientras tanto.
            $this->paso = 1;
            $this->pin = $this->pinRepetido = '';
            $this->addError('ci', __('jota.acceso.error_ya_activada'));

            return;
        }

        Auth::login($user, remember: true);
        session()->regenerate();
        session()->flash('bienvenida', strtok($user->name, ' '));

        $this->redirect(DestinoPorRol::para($user));
    }

    public function volver(): void
    {
        $this->paso = 1;
        $this->pin = $this->pinRepetido = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.acceso.activar')->title(__('jota.acceso.activar_titulo'));
    }
}
