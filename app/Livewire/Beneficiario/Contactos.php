<?php

namespace App\Livewire\Beneficiario;

use App\Domain\Contactos\ContactosDelParticipante;
use App\Enums\TipoContacto;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Registro JOTA o JOTI del participante: sus contactos con su estado. Solo lectura; cargar uno está en otra pantalla. */
#[Layout('components.layouts.movil')]
class Contactos extends Component
{
    #[Locked]
    public string $tipo = 'joti';

    public function mount(string $tipo): void
    {
        abort_unless(TipoContacto::tryFrom($tipo) !== null, 404);

        $this->tipo = $tipo;
    }

    public function render(ContactosDelParticipante $contactos)
    {
        $user = auth()->user();
        $tipo = TipoContacto::from($this->tipo);

        // Solo datos sueltos para la vista: ningún modelo queda en el estado de Livewire.
        $filas = collect($contactos->lista($user, $tipo))->map(fn ($c) => [
            'id' => $c->getKey(),
            'titulo' => $tipo->esJota() ? $c->estacion : "{$c->ciudad}, {$c->pais}",
            'detalle' => $tipo->esJota()
                ? collect([$c->contactado_at->format('d/m H:i'), "{$c->frecuencia} MHz {$c->emision}", "RST {$c->rst_tx} / {$c->rst_rx}"])->implode(' · ')
                : "{$c->grupo_scout} · {$c->unidad_scout}",
            'estado' => $c->estado->value,
            'local' => $c->es_local,
            'puntos' => $c->puntos,
            'motivo' => $c->motivo_rechazo,
            'quien' => $tipo->esJota() && $c->user_id !== $user->getKey() ? $c->user->name : null,
        ])->all();

        $cuenta = $contactos->resumen($user)[$tipo->value];

        return view('livewire.beneficiario.contactos', [
            'filas' => $filas,
            'aprobados' => $cuenta['aprobados'],
            'pendientes' => $cuenta['pendientes'],
            'esJota' => $tipo->esJota(),
            'porUnidad' => $tipo->esJota() && ! $user->patrulla_id,
        ])->title(__('jota.contactos.registro_'.$tipo->value));
    }
}
