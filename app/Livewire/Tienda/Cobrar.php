<?php

namespace App\Livewire\Tienda;

use App\Domain\Tienda\CancelarCobro;
use App\Domain\Tienda\CobroYaPagado;
use App\Domain\Tienda\CrearCobro;
use App\Domain\Tienda\QrCobro;
use App\Models\Cobro;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Cobrar: se arma el monto con el teclado, se genera el QR y la pantalla espera el pago. */
#[Layout('components.layouts.movil')]
class Cobrar extends Component
{
    /** Dígitos tecleados (los arma el teclado de la página y viajan al enviar). */
    public string $monto = '';

    #[Locked]
    public ?int $cobroId = null;

    public string $error = '';

    public function generar(CrearCobro $crear): void
    {
        $this->error = '';

        if (! ctype_digit($this->monto) || (int) $this->monto <= 0) {
            $this->error = __('jota.tienda.error_monto');

            return;
        }

        try {
            $this->cobroId = $crear(auth()->user(), (int) $this->monto)->getKey();
            $this->monto = '';
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function cancelar(CancelarCobro $cancelar): void
    {
        $cobro = $this->cobro();

        if (! $cobro) {
            return;
        }

        try {
            $cancelar(auth()->user(), $cobro);
            $this->cobroId = null;
        } catch (CobroYaPagado) {
            // Alguien pagó justo antes: se muestra el pago, no se cancela nada.
        } catch (AuthorizationException) {
            abort(403);
        }
    }

    public function nuevo(): void
    {
        $this->cobroId = null;
        $this->monto = '';
        $this->error = '';
    }

    private function cobro(): ?Cobro
    {
        return $this->cobroId
            ? Cobro::query()->with('pagador:id,name')->where('creado_por', auth()->id())->find($this->cobroId)
            : null;
    }

    public function render(QrCobro $qr)
    {
        $cobro = $this->cobro();
        $estado = $cobro?->estado();

        return view('livewire.tienda.cobrar', [
            'estado' => $estado,
            'montoCobro' => $cobro?->monto,
            'pagadoPor' => $cobro?->pagador?->name,
            'pagadoA' => $cobro?->pagado_at,
            'segundos' => $cobro?->segundosRestantes() ?? 0,
            'qr' => $estado === Cobro::PENDIENTE ? $qr->svg($cobro) : null,
        ])->title(__('jota.tienda.cobrar_titulo'));
    }
}
