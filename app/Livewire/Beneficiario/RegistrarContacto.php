<?php

namespace App\Livewire\Beneficiario;

use App\Domain\Contactos\ContactoInvalido;
use App\Domain\Contactos\RegistrarContactoJota;
use App\Domain\Contactos\RegistrarContactoJoti;
use App\Domain\Misiones\EntregaInvalida;
use App\Domain\Misiones\ImagenEntrega;
use App\Enums\TipoContacto;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Formulario para cargar un contacto JOTA (libro de guardia) o JOTI (datos del grupo y captura). La decisión final es del servidor. */
#[Layout('components.layouts.movil')]
class RegistrarContacto extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $tipo = 'joti';

    // JOTI
    public string $grupo_scout = '';

    public string $ciudad = '';

    public string $unidad_scout = '';

    /** paraguay | otro */
    public string $procedencia = 'paraguay';

    public string $pais_otro = '';

    public $captura = null;

    // JOTA
    public string $fecha = '';

    public string $hora = '';

    public string $estacion = '';

    public string $rst_tx = '';

    public string $rst_rx = '';

    public string $frecuencia = '';

    public string $emision = '';

    public string $obs = '';

    public bool $qsl_r = false;

    public bool $qsl_e = false;

    public bool $enviada = false;

    public string $error = '';

    public function mount(string $tipo): void
    {
        abort_unless(TipoContacto::tryFrom($tipo) !== null, 404);

        $this->tipo = $tipo;
        $this->reiniciar();
    }

    private function reiniciar(): void
    {
        // La fecha y la hora que se proponen son las del servidor, que es la que vale.
        $this->fecha = now()->format('Y-m-d');
        $this->hora = now()->format('H:i');
    }

    public function updatedCaptura(): void
    {
        $this->resetErrorBag('captura');

        try {
            ImagenEntrega::validar($this->captura);
        } catch (EntregaInvalida $e) {
            $this->captura = null;
            $this->addError('captura', $e->getMessage());
        }
    }

    public function quitarCaptura(): void
    {
        $this->captura = null;
        $this->resetErrorBag('captura');
    }

    public function enviar(RegistrarContactoJota $jota, RegistrarContactoJoti $joti): void
    {
        $this->error = '';
        $this->resetErrorBag();

        try {
            TipoContacto::from($this->tipo)->esJota()
                ? $jota(auth()->user(), [
                    'contactado_at' => trim("{$this->fecha} {$this->hora}"),
                    'estacion' => $this->estacion, 'rst_tx' => $this->rst_tx, 'rst_rx' => $this->rst_rx,
                    'frecuencia' => $this->frecuencia, 'emision' => $this->emision, 'obs' => $this->obs,
                    'qsl_r' => $this->qsl_r, 'qsl_e' => $this->qsl_e,
                ])
                : $joti(auth()->user(), [
                    'grupo_scout' => $this->grupo_scout, 'ciudad' => $this->ciudad, 'unidad_scout' => $this->unidad_scout,
                    'pais' => $this->procedencia === 'paraguay' ? 'Paraguay' : $this->pais_otro,
                ], $this->captura);
        } catch (ContactoInvalido $e) {
            $this->error = $e->getMessage();

            return;
        } catch (AuthorizationException) {
            abort(403);
        }

        $this->enviada = true;
        $this->captura = null;
    }

    public function otro(): void
    {
        $this->reset('grupo_scout', 'ciudad', 'unidad_scout', 'pais_otro', 'captura', 'estacion', 'rst_tx', 'rst_rx', 'frecuencia', 'emision', 'obs', 'qsl_r', 'qsl_e', 'enviada', 'error');
        $this->procedencia = 'paraguay';
        $this->reiniciar();
    }

    public function render()
    {
        $vista = null;

        if ($this->captura) {
            try {
                $vista = $this->captura->temporaryUrl();
            } catch (\Throwable) {
                $vista = null;
            }
        }

        return view('livewire.beneficiario.registrar-contacto', [
            'esJota' => TipoContacto::from($this->tipo)->esJota(),
            'emisiones' => array_combine(RegistrarContactoJota::EMISIONES, RegistrarContactoJota::EMISIONES),
            'vista' => $vista,
        ])->title(__('jota.contactos.form_titulo', ['tipo' => TipoContacto::from($this->tipo)->getLabel()]));
    }
}
