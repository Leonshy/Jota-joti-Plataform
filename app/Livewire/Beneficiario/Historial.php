<?php

namespace App\Livewire\Beneficiario;

use App\Domain\Inicio\HistorialDelParticipante;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.movil')]
class Historial extends Component
{
    private const PASO = 20;

    #[Url(as: 'ver')]
    public string $filtro = 'todo';

    public int $limite = self::PASO;

    public function elegir(string $filtro): void
    {
        $this->filtro = in_array($filtro, ['todo', 'puntos', 'jlis'], true) ? $filtro : 'todo';
        $this->limite = self::PASO;
    }

    public function cargarMas(): void
    {
        $this->limite += self::PASO;
    }

    public function render(HistorialDelParticipante $historial)
    {
        $filtro = in_array($this->filtro, ['todo', 'puntos', 'jlis'], true) ? $this->filtro : 'todo';

        return view('livewire.beneficiario.historial', [
            'h' => $historial(auth()->user(), $filtro, min($this->limite, 500)),
            'filtroActual' => $filtro,
        ])->title(__('jota.historial.titulo'));
    }
}
