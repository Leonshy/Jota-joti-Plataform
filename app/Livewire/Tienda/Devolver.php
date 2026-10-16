<?php

namespace App\Livewire\Tienda;

use App\Domain\Tienda\BuscarParticipante;
use App\Domain\Tienda\DevolucionExcedeCompras;
use App\Domain\Tienda\Devolver as DevolverJlis;
use App\Domain\Tienda\ResumenCompras;
use App\Enums\Rol;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Devolver LisCoins: se busca a la persona, se ve cuánto gastó y cuánto se le puede devolver, y se devuelve con motivo. */
#[Layout('components.layouts.movil')]
class Devolver extends Component
{
    public string $buscar = '';

    #[Locked]
    public ?int $personaId = null;

    public string $monto = '';

    public string $motivo = '';

    /** @var array{nombre: string, monto: string, saldo: string}|null */
    public ?array $resultado = null;

    public function elegir(int $id): void
    {
        $this->personaId = User::query()->where('rol', Rol::Beneficiario)->findOrFail($id)->getKey();
        $this->buscar = '';
        $this->monto = $this->motivo = '';
        $this->resetErrorBag();
    }

    public function cambiar(): void
    {
        $this->personaId = null;
        $this->monto = $this->motivo = '';
        $this->resetErrorBag();
    }

    public function nueva(): void
    {
        $this->resultado = null;
        $this->cambiar();
    }

    public function devolver(DevolverJlis $devolver): void
    {
        $this->resetErrorBag();

        if (! $this->personaId) {
            return;
        }

        if (! ctype_digit($this->monto) || (int) $this->monto <= 0) {
            $this->addError('monto', __('jota.tienda.error_monto'));

            return;
        }

        if (trim($this->motivo) === '') {
            $this->addError('motivo', __('jota.dirigente.error_motivo'));

            return;
        }

        $persona = User::query()->findOrFail($this->personaId);

        try {
            $devolver(auth()->user(), $persona, (int) $this->monto, $this->motivo);
        } catch (DevolucionExcedeCompras $e) {
            $this->addError('monto', $e->maximo > 0 ? __('jota.tienda.error_tope', ['max' => cantidad($e->maximo, 'jli')]) : __('jota.tienda.nada_texto'));

            return;
        } catch (InvalidArgumentException $e) {
            $this->addError('monto', $e->getMessage());

            return;
        } catch (AuthorizationException) {
            abort(403);
        }

        $this->resultado = ['nombre' => $persona->name, 'monto' => cantidad((int) $this->monto, 'jli'), 'saldo' => cantidad($persona->fresh()->jlis, 'jli')];
        $this->cambiar();
    }

    public function render(BuscarParticipante $buscador, ResumenCompras $resumen)
    {
        $persona = $this->personaId ? User::query()->with('patrulla:id,nombre')->find($this->personaId) : null;

        $resultados = [];
        if (! $persona && trim($this->buscar) !== '') {
            $resultados = $buscador(auth()->user(), $this->buscar)->load('patrulla:id,nombre')
                ->map(fn (User $u) => ['id' => $u->getKey(), 'nombre' => $u->name, 'detalle' => 'CI '.$u->ci.($u->patrulla ? ' · '.$u->patrulla->nombre : '')])->all();
        }

        return view('livewire.tienda.devolver', [
            'persona' => $persona ? ['nombre' => $persona->name, 'detalle' => 'CI '.$persona->ci.($persona->patrulla ? ' · '.$persona->patrulla->nombre : '')] : null,
            'compras' => $persona ? $resumen($persona) : null,
            'resultados' => $resultados,
        ])->title(__('jota.tienda.devolver_titulo'));
    }
}
