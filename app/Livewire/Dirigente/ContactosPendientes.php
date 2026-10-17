<?php

namespace App\Livewire\Dirigente;

use App\Domain\Contactos\ColaContactos;
use Carbon\CarbonInterface;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Contactos JOTA y JOTI pendientes de la unidad del dirigente (o de la que elija el admin), los más antiguos primero. */
#[Layout('components.layouts.movil')]
class ContactosPendientes extends Component
{
    public function render(ColaContactos $cola)
    {
        $contactos = $cola(auth()->user(), unidad_activa())->limit(100)->get();

        // Solo datos sueltos para la vista: ningún modelo queda en el estado de Livewire.
        $filas = $contactos->map(fn ($c) => [
            'id' => $c->getKey(),
            'tipo' => $c->tipo->value,
            'titulo' => $c->esJota() ? "{$c->estacion} · ".__('jota.inicio.tu_patrulla', ['nombre' => $c->patrulla?->nombre ?? $c->unidad?->nombre]) : "{$c->ciudad}, {$c->pais}",
            'subtitulo' => collect([
                $c->esJota() ? null : $c->user->name,
                $c->esJota() ? $c->user->name : $c->grupo_scout,
                __('jota.dirigente.hace', ['tiempo' => $c->created_at->locale('es')->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE, short: true)]),
            ])->filter()->implode(' · '),
        ])->all();

        return view('livewire.dirigente.contactos-pendientes', ['filas' => $filas])->title(__('jota.contactos.dir_titulo'));
    }
}
