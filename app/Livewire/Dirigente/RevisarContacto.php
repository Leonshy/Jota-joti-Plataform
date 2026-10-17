<?php

namespace App\Livewire\Dirigente;

use App\Domain\Contactos\AprobarContacto;
use App\Domain\Contactos\ColaContactos;
use App\Domain\Contactos\ContactoInvalido;
use App\Domain\Contactos\ContactoYaRevisado;
use App\Domain\Contactos\Puntaje;
use App\Domain\Contactos\PuntajeSinConfigurar;
use App\Domain\Contactos\RechazarContacto;
use App\Enums\EstadoEntrega;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Revisar un contacto: aprobar acredita el puntaje del admin; rechazar pide el motivo y es definitivo. */
#[Layout('components.layouts.movil')]
class RevisarContacto extends Component
{
    #[Locked]
    public int $contactoId;

    /** ver | rechazar | aprobada | rechazada | ya */
    public string $paso = 'ver';

    public string $motivo = '';

    public bool $sinPuntaje = false;

    public function mount(Contacto $contacto): void
    {
        Gate::authorize('revisar', $contacto);

        $this->contactoId = $contacto->getKey();
        $this->paso = $contacto->estado === EstadoEntrega::Pendiente ? 'ver' : 'ya';
    }

    public function aprobar(AprobarContacto $aprobar): void
    {
        $this->sinPuntaje = false;

        try {
            $aprobar(auth()->user(), $this->contacto());
            $this->paso = 'aprobada';
        } catch (ContactoYaRevisado) {
            $this->paso = 'ya';
        } catch (PuntajeSinConfigurar) {
            $this->sinPuntaje = true;
        } catch (AuthorizationException) {
            abort(403);
        }
    }

    public function pedirRechazo(): void
    {
        $this->paso = 'rechazar';
    }

    public function cancelarRechazo(): void
    {
        $this->paso = 'ver';
        $this->resetErrorBag();
    }

    public function rechazar(RechazarContacto $rechazar): void
    {
        $this->resetErrorBag();

        try {
            $rechazar(auth()->user(), $this->contacto(), $this->motivo);
            $this->paso = 'rechazada';
        } catch (ContactoInvalido) {
            $this->addError('motivo', __('jota.contactos.dir_motivo_error'));
        } catch (ContactoYaRevisado) {
            $this->paso = 'ya';
        } catch (AuthorizationException) {
            abort(403);
        }
    }

    private function contacto(): Contacto
    {
        return Contacto::query()->findOrFail($this->contactoId);
    }

    public function render(ColaContactos $cola, Puntaje $puntaje)
    {
        $c = Contacto::query()->with(['user:id,name', 'patrulla:id,nombre', 'unidad:id,nombre'])->findOrFail($this->contactoId);
        $puntos = $puntaje->para($c->tipo, $c->es_local);

        $integrantes = $c->esJota() && $c->patrulla_id ? User::query()->where('patrulla_id', $c->patrulla_id)->count() : 0;
        $siguiente = in_array($this->paso, ['aprobada', 'rechazada', 'ya'], true)
            ? $cola(auth()->user(), unidad_activa())->whereKeyNot($c->getKey())->value('contactos.id')
            : null;

        $datos = $c->esJota()
            ? [
                'estacion' => $c->estacion,
                'fecha_hora' => $c->contactado_at->format('d/m/Y H:i'),
                'rst' => "{$c->rst_tx} / {$c->rst_rx}",
                'frecuencia' => "{$c->frecuencia} MHz",
                'emision' => $c->emision,
                'obs' => $c->obs,
                'qsl_r' => $c->qsl_r,
                'qsl_e' => $c->qsl_e,
            ]
            : ['grupo_scout' => $c->grupo_scout, 'ciudad' => $c->ciudad, 'unidad_scout' => $c->unidad_scout, 'pais' => $c->pais];

        return view('livewire.dirigente.revisar-contacto', [
            'esJota' => $c->esJota(),
            'tipo' => $c->tipo->value,
            'local' => $c->es_local,
            'quien' => $c->user->name,
            'nombre' => strtok($c->user->name, ' '),
            'equipo' => $c->patrulla?->nombre ?? $c->unidad?->nombre,
            'porUnidad' => $c->esJota() && ! $c->patrulla_id,
            'datos' => $datos,
            'puntos' => $puntos,
            'cuota' => $puntos !== null && $integrantes > 0 ? (int) ceil($puntos / $integrantes) : 0,
            'captura' => ! $c->esJota() && $c->captura_ruta ? ($c->captura_procesada_at ? route('contactos.captura', $c) : false) : null,
            'estadoActual' => $c->estado,
            'siguiente' => $siguiente,
        ])->title(__('jota.contactos.dir_revisar_titulo'));
    }
}
