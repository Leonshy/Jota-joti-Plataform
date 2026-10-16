<?php

namespace App\Livewire\Beneficiario;

use App\Domain\Misiones\MisionesDelParticipante;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.movil')]
class Misiones extends Component
{
    public function render(MisionesDelParticipante $misiones)
    {
        return view('livewire.beneficiario.misiones', [
            'misiones' => $misiones->lista(auth()->user()),
        ])->title(__('jota.misiones.titulo'));
    }
}
