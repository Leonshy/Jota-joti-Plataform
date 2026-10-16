<?php

namespace App\Livewire\Dirigente;

use App\Domain\Actividades\CerrarSesionAsistencia;
use App\Domain\Actividades\QrAsistencia;
use App\Models\SesionAsistencia;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Sesión de asistencia abierta: cuántos marcaron, el QR en pantalla completa y el cierre. */
#[Layout('components.layouts.movil')]
class Sesion extends Component
{
    #[Locked]
    public int $sesionId;

    public bool $confirmandoCierre = false;

    public function mount(SesionAsistencia $sesion): void
    {
        Gate::authorize('gestionar', $sesion->actividad);

        $this->sesionId = $sesion->getKey();
    }

    public function pedirCierre(): void
    {
        $this->confirmandoCierre = true;
    }

    public function seguirAbierta(): void
    {
        $this->confirmandoCierre = false;
    }

    public function cerrar(CerrarSesionAsistencia $cerrar): void
    {
        $cerrar(auth()->user(), SesionAsistencia::query()->findOrFail($this->sesionId));
        $this->confirmandoCierre = false;
    }

    public function render(QrAsistencia $qr)
    {
        $sesion = SesionAsistencia::query()->with('actividad:id,titulo,puntos')->withCount('asistencias')->findOrFail($this->sesionId);

        return view('livewire.dirigente.sesion', [
            'titulo' => $sesion->actividad->titulo,
            'abierta' => $sesion->estaAbierta(),
            'asistentes' => $sesion->asistencias_count,
            'urlPublica' => $sesion->estaAbierta() ? $sesion->urlPublica() : null,
            // El QR vigente se vuelve a dibujar con cada refresco (cada 5 s), así siempre es el de la ventana actual.
            'qr' => $sesion->estaAbierta() ? $qr->svg($sesion) : null,
        ])->title($sesion->actividad->titulo);
    }
}
