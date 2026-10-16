<?php

namespace App\Livewire\Dirigente;

use App\Domain\Misiones\AprobarEntrega;
use App\Domain\Misiones\ColaValidacion;
use App\Domain\Misiones\EntregaInvalida;
use App\Domain\Misiones\EntregaYaRevisada;
use App\Domain\Misiones\RechazarEntrega;
use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Revisar una entrega: aprobar acredita; rechazar pide el motivo. Quien llega segundo ve «ya revisada». */
#[Layout('components.layouts.movil')]
class Revisar extends Component
{
    #[Locked]
    public int $entregaId;

    /** ver | rechazar | aprobada | rechazada | ya */
    public string $paso = 'ver';

    public string $motivo = '';

    public function mount(Entrega $entrega): void
    {
        Gate::authorize('revisar', $entrega);

        $this->entregaId = $entrega->getKey();
        $this->paso = $entrega->estado === EstadoEntrega::Pendiente ? 'ver' : 'ya';
    }

    public function aprobar(AprobarEntrega $aprobar): void
    {
        try {
            $aprobar(auth()->user(), $this->entrega());
            $this->paso = 'aprobada';
        } catch (EntregaYaRevisada) {
            $this->paso = 'ya';
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

    public function rechazar(RechazarEntrega $rechazar): void
    {
        $this->resetErrorBag();

        try {
            $rechazar(auth()->user(), $this->entrega(), $this->motivo);
            $this->paso = 'rechazada';
        } catch (EntregaInvalida) {
            $this->addError('motivo', __('jota.dirigente.error_motivo'));
        } catch (EntregaYaRevisada) {
            $this->paso = 'ya';
        } catch (AuthorizationException) {
            abort(403);
        }
    }

    private function entrega(): Entrega
    {
        return Entrega::query()->findOrFail($this->entregaId);
    }

    public function render(ColaValidacion $cola)
    {
        $entrega = Entrega::query()
            ->with(['mision', 'user:id,name,patrulla_id', 'user.patrulla:id,nombre', 'patrulla:id,nombre', 'imagenes'])
            ->findOrFail($this->entregaId);
        $mision = $entrega->mision;
        $porPatrulla = $mision->esPorPatrulla();

        $integrantes = $porPatrulla ? User::query()->where('patrulla_id', $entrega->patrulla_id)->count() : 0;
        $siguiente = in_array($this->paso, ['aprobada', 'rechazada', 'ya'], true)
            ? $cola(auth()->user(), unidad_activa())->whereKeyNot($entrega->getKey())->value('entregas.id')
            : null;

        return view('livewire.dirigente.revisar', [
            'porPatrulla' => $porPatrulla,
            'titulo' => $porPatrulla ? __('jota.inicio.tu_patrulla', ['nombre' => $entrega->patrulla?->nombre]) : $entrega->user->name,
            'nombre' => strtok($entrega->user->name, ' '),
            'subtitulo' => $porPatrulla
                ? __('jota.dirigente.entregada_por', ['nombre' => $entrega->user->name])
                : $entrega->user->patrulla?->nombre,
            'mision' => $mision->titulo,
            'puntos' => $mision->puntos,
            'cuota' => $porPatrulla && $integrantes > 0 ? (int) ceil($mision->puntos / $integrantes) : 0,
            'texto' => $entrega->texto,
            'imagenes' => $entrega->imagenes->map(fn ($i) => $i->procesada_at ? route('entregas.imagen', $i) : null)->all(),
            'estadoActual' => $entrega->estado,
            'siguiente' => $siguiente,
        ])->title(__('jota.dirigente.revisar_titulo'));
    }
}
