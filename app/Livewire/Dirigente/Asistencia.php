<?php

namespace App\Livewire\Dirigente;

use App\Domain\Actividades\AbrirSesionAsistencia;
use App\Domain\Actividades\ModalidadIncorrecta;
use App\Domain\Actividades\SesionYaAbierta;
use App\Models\Actividad;
use App\Models\SesionAsistencia;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Actividades de la unidad: abrir la sesión de asistencia (individuales) o ir a marcar cumplidas (por equipo). */
#[Layout('components.layouts.movil')]
class Asistencia extends Component
{
    public string $error = '';

    public function abrir(int $actividadId, AbrirSesionAsistencia $abrir): void
    {
        $this->error = '';
        $user = auth()->user();
        $actividad = Actividad::query()
            ->whereHas('unidades', fn (Builder $q) => $q->whereKey(unidad_activa()))
            ->findOrFail($actividadId);

        try {
            $sesion = $abrir($user, $actividad);
        } catch (SesionYaAbierta $e) {
            // Otra persona (u otro celular) ya la había abierto: se sigue con esa.
            $sesion = $e->sesion;
        } catch (ModalidadIncorrecta $e) {
            $this->error = $e->getMessage();

            return;
        } catch (AuthorizationException) {
            abort(403);
        }

        $this->redirectRoute('dirigente.sesion', $sesion);
    }

    public function render()
    {
        $user = auth()->user();
        $actividades = Actividad::query()
            ->whereHas('unidades', fn (Builder $q) => $q->whereKey(unidad_activa()))
            ->orderBy('titulo')
            ->get();

        $abiertas = SesionAsistencia::query()
            ->whereIn('actividad_id', $actividades->modelKeys())
            ->whereNull('cerrada_at')
            ->withCount('asistencias')
            ->get()
            ->keyBy('actividad_id');

        $filas = $actividades->map(fn (Actividad $a) => [
            'id' => $a->getKey(),
            'titulo' => $a->titulo,
            'por_equipo' => $a->esPorEquipo(),
            'puntos' => $a->puntos,
            'sesion' => $abiertas->get($a->getKey())?->getKey(),
            'asistentes' => $abiertas->get($a->getKey())?->asistencias_count,
        ])->all();

        return view('livewire.dirigente.asistencia', ['filas' => $filas])->title(__('jota.dirigente.asistencia_titulo'));
    }
}
