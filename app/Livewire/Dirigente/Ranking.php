<?php

namespace App\Livewire\Dirigente;

use App\Domain\Ranking\RankingUnidad;
use App\Models\Unidad;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Ranking de la unidad del dirigente: solo puesto y puntaje, sin nombres (D6). */
#[Layout('components.layouts.movil')]
class Ranking extends Component
{
    public function render(RankingUnidad $ranking)
    {
        $user = auth()->user();

        return view('livewire.dirigente.ranking', [
            'vista' => $ranking->vistaDirigente($user, unidad_activa()),
            'unidad' => Unidad::query()->whereKey(unidad_activa())->value('nombre'),
        ])->title(__('jota.navegacion.ranking'));
    }
}
