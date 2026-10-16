<?php

namespace App\Livewire\Beneficiario;

use App\Domain\Inicio\ResumenParticipante;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.movil')]
class Inicio extends Component
{
    public function render(ResumenParticipante $resumen)
    {
        $user = auth()->user();

        // Los datos no se guardan en propiedades públicas: solo se dibujan (no viajan al navegador como estado).
        return view('livewire.beneficiario.inicio', [
            'nombre' => strtok($user->name, ' '),
            'resumen' => $resumen($user),
            'bienvenida' => session('bienvenida'),
        ])->title(__('jota.navegacion.inicio'));
    }
}
