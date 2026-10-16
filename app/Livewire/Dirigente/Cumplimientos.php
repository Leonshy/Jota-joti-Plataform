<?php

namespace App\Livewire\Dirigente;

use App\Domain\Actividades\CumplimientoYaAcreditado;
use App\Domain\Actividades\EquipoDeReserva;
use App\Domain\Actividades\MarcarCumplimiento;
use App\Domain\Actividades\ModalidadIncorrecta;
use App\Domain\Actividades\PatrullaFueraDeActividad;
use App\Models\Actividad;
use App\Models\CumplimientoEquipo;
use App\Models\Patrulla;
use App\Models\Unidad;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Actividades por equipo: el dirigente marca cada patrulla de su unidad como cumplida o no cumplida. */
#[Layout('components.layouts.movil')]
class Cumplimientos extends Component
{
    #[Locked]
    public ?int $actividadId = null;

    /** Equipo que se está por marcar como cumplido, «p12» (patrulla) o «u3» (toda la unidad): pide confirmación porque acredita y no se deshace. */
    public ?string $confirmar = null;

    public string $mensaje = '';

    public function mount(?Actividad $actividad = null): void
    {
        if ($actividad) {
            Gate::authorize('gestionar', $actividad);
            abort_unless($actividad->esPorEquipo(), 404);
            $this->actividadId = $actividad->getKey();
        }
    }

    public function pedirCumplida(string $equipo): void
    {
        $this->confirmar = $equipo;
        $this->mensaje = '';
    }

    public function noConfirmar(): void
    {
        $this->confirmar = null;
    }

    public function cumplida(MarcarCumplimiento $marcar): void
    {
        $id = $this->confirmar;
        $this->confirmar = null;

        if ($id) {
            $this->marcar($marcar, $id, true);
        }
    }

    public function noCumplida(string $equipo, MarcarCumplimiento $marcar): void
    {
        $this->marcar($marcar, $equipo, false);
    }

    private function marcar(MarcarCumplimiento $marcar, string $equipo, bool $cumplida): void
    {
        $this->mensaje = '';
        $unidadId = unidad_activa();
        $id = (int) substr($equipo, 1);

        // Solo equipos de la unidad con la que se está trabajando: un id de otra unidad no existe para este dirigente.
        $destino = match (substr($equipo, 0, 1)) {
            'u' => Unidad::query()->whereKey($unidadId)->where('id', $id)->firstOrFail(),
            default => Patrulla::query()->where('unidad_id', $unidadId)->findOrFail($id),
        };

        try {
            $marcar(auth()->user(), Actividad::query()->findOrFail($this->actividadId), $destino, $cumplida);
        } catch (CumplimientoYaAcreditado) {
            $this->mensaje = __('jota.dirigente.cumplida_ya');
        } catch (ModalidadIncorrecta|PatrullaFueraDeActividad $e) {
            $this->mensaje = $e->getMessage();
        } catch (AuthorizationException) {
            abort(403);
        }
    }

    public function render()
    {
        $user = auth()->user();

        if (! $this->actividadId) {
            $lista = Actividad::query()
                ->where('modalidad', 'patrulla')
                ->whereHas('unidades', fn (Builder $q) => $q->whereKey(unidad_activa()))
                ->orderBy('titulo')
                ->get(['id', 'titulo', 'puntos'])
                ->map(fn ($a) => ['id' => $a->id, 'titulo' => $a->titulo, 'puntos' => $a->puntos])
                ->all();

            return view('livewire.dirigente.cumplimientos', ['actividad' => null, 'lista' => $lista, 'patrullas' => []])
                ->title(__('jota.dirigente.cumplimientos_titulo'));
        }

        $actividad = Actividad::query()->findOrFail($this->actividadId);
        $marcas = CumplimientoEquipo::query()->where('actividad_id', $actividad->getKey())->get()
            ->mapWithKeys(fn (CumplimientoEquipo $c) => [($c->unidad_id ? 'u'.$c->unidad_id : 'p'.$c->patrulla_id) => $c->cumplida]);
        $estado = fn (string $clave) => $marcas->has($clave) ? ($marcas[$clave] ? 'cumplida' : 'no_cumplida') : 'sin_marcar';

        if (EquipoDeReserva::unidadEsEquipo($unidadId = unidad_activa())) {
            // Los Rovers hacen la estación todos juntos: un solo equipo, y cada uno recibe los puntos completos.
            $unidad = Unidad::query()->findOrFail($unidadId);
            $equipos = [[
                'id' => 'u'.$unidad->getKey(),
                'nombre' => __('jota.dirigente.toda_la_unidad', ['nombre' => $unidad->nombre]),
                'estado' => $estado('u'.$unidad->getKey()),
                'cuota' => $actividad->puntos,
                'cada_uno' => true,
            ]];
        } else {
            $equipos = Patrulla::query()
                ->where('unidad_id', $unidadId)
                ->withCount('integrantes')
                ->orderBy('nombre')
                ->get()
                ->map(fn (Patrulla $p) => [
                    'id' => 'p'.$p->getKey(),
                    'nombre' => $p->nombre,
                    'estado' => $estado('p'.$p->getKey()),
                    'cuota' => $p->integrantes_count > 0 ? (int) ceil($actividad->puntos / $p->integrantes_count) : 0,
                    'cada_uno' => false,
                ])->all();
        }

        return view('livewire.dirigente.cumplimientos', ['actividad' => ['titulo' => $actividad->titulo, 'puntos' => $actividad->puntos], 'lista' => [], 'patrullas' => $equipos])
            ->title($actividad->titulo);
    }
}
