<?php

namespace App\Livewire\Beneficiario;

use App\Domain\Ranking\RankingUnidad;
use App\Models\Unidad;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.movil')]
class Ranking extends Component
{
    public function render(RankingUnidad $ranking)
    {
        $user = auth()->user();

        // CLAUDE.md §2.8: solo se pasa a la vista el arreglo anónimo (puesto y puntaje) y los puntos propios.
        // Nada de modelos ni de la caché del admin, y nada queda en propiedades públicas.
        return view('livewire.beneficiario.ranking', [
            'vista' => $ranking->vistaBeneficiario($user),
            'unidad' => Unidad::query()->whereKey($user->unidad_id)->value('nombre'),
        ])->title(__('jota.navegacion.ranking'));
    }
}
