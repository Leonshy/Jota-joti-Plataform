<?php

namespace App\Livewire\Dirigente;

use App\Domain\Contactos\ColaContactos;
use App\Domain\Misiones\ColaValidacion;
use Carbon\CarbonInterface;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Entregas pendientes de la unidad del dirigente, las más antiguas primero. */
#[Layout('components.layouts.movil')]
class Cola extends Component
{
    public function render(ColaValidacion $cola, ColaContactos $colaContactos)
    {
        $entregas = $cola(auth()->user(), unidad_activa())->limit(100)->get();

        // Solo datos sueltos para la vista: ningún modelo queda en el estado de Livewire.
        $filas = $entregas->map(fn ($e) => [
            'id' => $e->getKey(),
            'titulo' => $e->mision->esPorPatrulla() ? __('jota.inicio.tu_patrulla', ['nombre' => $e->patrulla?->nombre]) : $e->user->name,
            'subtitulo' => collect([
                $e->mision->esPorPatrulla() ? null : $e->user->patrulla?->nombre,
                $e->mision->titulo,
                __('jota.dirigente.hace', ['tiempo' => $e->created_at->locale('es')->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE, short: true)]),
            ])->filter()->implode(' · '),
            'fotos' => $e->imagenes->count(),
            'por_patrulla' => $e->mision->esPorPatrulla(),
        ])->all();

        return view('livewire.dirigente.cola', ['filas' => $filas, 'contactos' => $colaContactos(auth()->user(), unidad_activa())->count()])->title(__('jota.dirigente.cola_titulo'));
    }
}
