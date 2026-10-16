<?php

namespace App\Livewire\Dirigente;

use App\Domain\Penalizaciones\AnularPenalizacion;
use App\Domain\Penalizaciones\PenalizacionYaAnulada;
use App\Domain\Penalizaciones\Penalizar;
use App\Enums\Rol;
use App\Models\Patrulla;
use App\Models\Penalizacion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Penalizar a una persona o a una patrulla de la unidad (resta puntos, no toca LisCoins) y anular las mal puestas. */
#[Layout('components.layouts.movil')]
class Penalizaciones extends Component
{
    /** persona | patrulla */
    public string $tipo = 'persona';

    public string $buscar = '';

    #[Locked]
    public ?int $personaId = null;

    public string $patrullaId = '';

    public string $cantidad = '';

    public string $motivo = '';

    /** @var array{titulo: string, texto: string}|null */
    public ?array $resultado = null;

    public ?int $anulando = null;

    public string $motivoAnulacion = '';

    public function updatedTipo(): void
    {
        $this->reiniciarObjetivo();
    }

    public function elegir(int $id): void
    {
        $persona = $this->personasDeLaUnidad()->findOrFail($id);
        $this->personaId = $persona->getKey();
        $this->buscar = '';
    }

    public function cambiarPersona(): void
    {
        $this->reiniciarObjetivo();
    }

    private function reiniciarObjetivo(): void
    {
        $this->personaId = null;
        $this->patrullaId = '';
        $this->buscar = '';
        $this->resetErrorBag();
    }

    public function aplicar(Penalizar $penalizar): void
    {
        $this->resetErrorBag();
        $this->resultado = null;
        $autor = auth()->user();

        if ($this->tipo === 'persona' && ! $this->personaId) {
            $this->addError('buscar', __('jota.dirigente.error_persona'));

            return;
        }

        if ($this->tipo === 'patrulla' && $this->patrullaId === '') {
            $this->addError('patrullaId', __('jota.dirigente.error_patrulla'));

            return;
        }

        if (! ctype_digit($this->cantidad) || (int) $this->cantidad <= 0) {
            $this->addError('cantidad', __('jota.dirigente.error_cantidad'));

            return;
        }

        if (trim($this->motivo) === '') {
            $this->addError('motivo', __('jota.dirigente.error_motivo'));

            return;
        }

        try {
            if ($this->tipo === 'persona') {
                $persona = $this->personasDeLaUnidad()->findOrFail($this->personaId);
                $penalizar->persona($autor, $persona, (int) $this->cantidad, $this->motivo);
                $nombre = $persona->name;
                $puntos = $persona->fresh()->puntos;
            } else {
                $patrulla = $this->patrullasDeLaUnidad()->findOrFail((int) $this->patrullaId);
                $penalizar->patrulla($autor, $patrulla, (int) $this->cantidad, $this->motivo);
                $nombre = __('jota.inicio.tu_patrulla', ['nombre' => $patrulla->nombre]);
                $puntos = $patrulla->fresh()->puntos;
            }
        } catch (InvalidArgumentException $e) {
            $this->addError('cantidad', $e->getMessage());

            return;
        } catch (AuthorizationException) {
            abort(403);
        }

        $this->resultado = ['titulo' => __('jota.dirigente.penalizada_titulo'), 'texto' => __('jota.dirigente.penalizada_texto', ['nombre' => $nombre, 'puntos' => cantidad($puntos, 'punto')])];
        $this->reiniciarObjetivo();
        $this->cantidad = $this->motivo = '';
    }

    public function pedirAnular(int $id): void
    {
        $this->anulando = $id;
        $this->motivoAnulacion = '';
        $this->resetErrorBag('motivoAnulacion');
    }

    public function noAnular(): void
    {
        $this->anulando = null;
    }

    public function anular(AnularPenalizacion $anular): void
    {
        $this->resetErrorBag('motivoAnulacion');
        $penalizacion = $this->penalizacionesDeLaUnidad()->find($this->anulando);

        if (! $penalizacion) {
            $this->anulando = null;

            return;
        }

        try {
            $anular(auth()->user(), $penalizacion, $this->motivoAnulacion);
        } catch (InvalidArgumentException) {
            $this->addError('motivoAnulacion', __('jota.dirigente.error_motivo'));

            return;
        } catch (PenalizacionYaAnulada) {
            // ya estaba anulada: se muestra tal cual
        } catch (AuthorizationException) {
            abort(403);
        }

        $this->anulando = null;
    }

    /** @return Builder<User> */
    private function personasDeLaUnidad(): Builder
    {
        return User::query()->where('rol', Rol::Beneficiario)->where('unidad_id', unidad_activa());
    }

    /** @return Builder<Patrulla> */
    private function patrullasDeLaUnidad(): Builder
    {
        return Patrulla::query()->where('unidad_id', unidad_activa());
    }

    /** @return Builder<Penalizacion> */
    private function penalizacionesDeLaUnidad(): Builder
    {
        $unidad = unidad_activa();

        return Penalizacion::query()->where(fn (Builder $q) => $q
            ->whereHas('user', fn (Builder $u) => $u->where('unidad_id', $unidad))
            ->orWhereHas('patrulla', fn (Builder $p) => $p->where('unidad_id', $unidad)));
    }

    public function render()
    {
        $resultados = [];
        $buscar = trim($this->buscar);

        if ($this->tipo === 'persona' && ! $this->personaId && mb_strlen($buscar) >= 2) {
            $like = '%'.addcslashes($buscar, '%_\\').'%';
            $resultados = $this->personasDeLaUnidad()
                ->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('ci', 'like', $like))
                ->with('patrulla:id,nombre')->orderBy('name')->limit(6)->get()
                ->map(fn (User $u) => ['id' => $u->getKey(), 'nombre' => $u->name, 'detalle' => $u->patrulla?->nombre])->all();
        }

        $persona = $this->personaId ? $this->personasDeLaUnidad()->with('patrulla:id,nombre')->find($this->personaId) : null;

        $recientes = $this->penalizacionesDeLaUnidad()
            ->with(['user:id,name', 'patrulla:id,nombre'])
            ->latest('id')->limit(15)->get()
            ->map(fn (Penalizacion $p) => [
                'id' => $p->getKey(),
                'quien' => $p->patrulla_id ? __('jota.inicio.tu_patrulla', ['nombre' => $p->patrulla->nombre]) : $p->user->name,
                'cantidad' => $p->cantidad,
                'motivo' => $p->motivo,
                'cuando' => cuando($p->created_at),
                'anulada' => $p->estaAnulada(),
            ])->all();

        return view('livewire.dirigente.penalizaciones', [
            'resultados' => $resultados,
            'persona' => $persona ? ['nombre' => $persona->name, 'detalle' => $persona->patrulla?->nombre] : null,
            'patrullas' => $this->patrullasDeLaUnidad()->orderBy('nombre')->pluck('nombre', 'id')->all(),
            'recientes' => $recientes,
        ])->title(__('jota.dirigente.penalizaciones_titulo'));
    }
}
