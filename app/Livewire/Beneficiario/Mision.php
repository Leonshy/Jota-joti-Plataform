<?php

namespace App\Livewire\Beneficiario;

use App\Domain\Misiones\MisionesDelParticipante;
use App\Models\Mision as MisionModelo;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Detalle de una misión y de la entrega propia (o de la patrulla). */
#[Layout('components.layouts.movil')]
class Mision extends Component
{
    #[Locked]
    public int $misionId;

    public function mount(MisionModelo $mision): void
    {
        Gate::authorize('ver', $mision);

        $this->misionId = $mision->getKey();
    }

    public function render(MisionesDelParticipante $misiones)
    {
        $mision = MisionModelo::query()->findOrFail($this->misionId);
        $user = auth()->user();

        return view('livewire.beneficiario.mision', [
            'mision' => $mision,
            'detalle' => $misiones->detalle($user, $mision),
            'patrulla' => $mision->esPorPatrulla() ? $user->patrulla()->value('nombre') : null,
        ])->title($mision->titulo);
    }
}
