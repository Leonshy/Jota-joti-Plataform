<?php

namespace App\Livewire\Acceso;

use App\Domain\Acceso\CiNoRegistrado;
use App\Domain\Acceso\CuentaSinActivar;
use App\Domain\Acceso\DestinoPorRol;
use App\Domain\Acceso\LoginCiPin;
use App\Domain\Acceso\PinIncorrecto;
use App\Domain\Participantes\ReglasParticipante;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Ingreso con CI + PIN. Sin bloqueo ni límite de intentos (decisión de producto). */
#[Layout('components.layouts.movil')]
class Ingresar extends Component
{
    public string $ci = '';

    public string $pin = '';

    public bool $ayuda = false;

    public function mount(): void
    {
        $this->ci = (string) request()->query('ci', '');
    }

    public function ingresar(LoginCiPin $login): void
    {
        $this->resetErrorBag();
        $ci = ReglasParticipante::normalizarCi($this->ci);

        if ($ci === '') {
            $this->addError('ci', __('jota.acceso.error_ci_vacio'));

            return;
        }

        if (! preg_match('/^\d{4}$/', $this->pin)) {
            $this->addError('pin', __('jota.acceso.error_pin_formato'));

            return;
        }

        try {
            $user = $login($ci, $this->pin);
        } catch (CiNoRegistrado) {
            $this->addError('ci', __('jota.acceso.error_ci'));

            return;
        } catch (CuentaSinActivar) {
            // Todavía no creó su PIN: se lo lleva a crearlo con el CI ya escrito.
            $this->redirectRoute('activar', ['ci' => $ci]);

            return;
        } catch (PinIncorrecto) {
            $this->pin = '';
            $this->addError('pin', __('jota.acceso.error_pin'));

            return;
        }

        Auth::login($user, remember: true); // sesión larga: dura todo el evento
        session()->regenerate();

        // Si venía de escanear un QR con la cámara del sistema, vuelve a esa acción.
        $this->redirectIntended(DestinoPorRol::para($user));
    }

    public function render()
    {
        return view('livewire.acceso.ingresar')->title(__('jota.acceso.ingresar_titulo'));
    }
}
