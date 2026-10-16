<?php

namespace App\Livewire\Beneficiario;

use App\Domain\Misiones\EntregaBloqueada;
use App\Domain\Misiones\EntregaInvalida;
use App\Domain\Misiones\EntregarMision;
use App\Domain\Misiones\ImagenEntrega;
use App\Domain\Misiones\MisionCerrada;
use App\Domain\Misiones\SinPatrulla;
use App\Models\Mision;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/** Entrega de una misión: texto y/o hasta 4 fotos. La decisión final la toma siempre el servidor. */
#[Layout('components.layouts.movil')]
class Entregar extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $misionId;

    public string $texto = '';

    /** Lo que la persona acaba de elegir; se pasa a $fotos para poder sumar de a una. */
    public $nueva = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $fotos = [];

    public bool $enviada = false;

    public function mount(Mision $mision): void
    {
        Gate::authorize('entregar', $mision);

        $this->misionId = $mision->getKey();
    }

    public function updatedNueva(): void
    {
        $this->resetErrorBag(['fotos', 'nueva']);
        $archivo = $this->nueva;
        $this->nueva = null;

        if (count($this->fotos) >= ImagenEntrega::MAX_POR_ENTREGA) {
            $this->addError('fotos', __('jota.misiones.error_muchas_fotos'));

            return;
        }

        try {
            ImagenEntrega::validar($archivo);
        } catch (EntregaInvalida $e) {
            $this->addError('fotos', $this->mensaje($e));

            return;
        }

        $this->fotos[] = $archivo;
    }

    public function quitar(int $indice): void
    {
        unset($this->fotos[$indice]);
        $this->fotos = array_values($this->fotos);
        $this->resetErrorBag('fotos');
    }

    public function enviar(EntregarMision $entregar): void
    {
        $this->resetErrorBag();
        $mision = Mision::query()->findOrFail($this->misionId);

        try {
            $entregar(auth()->user(), $mision, $this->texto, $this->fotos);
        } catch (EntregaInvalida $e) {
            $this->addError('texto', $this->mensaje($e));

            return;
        } catch (SinPatrulla) {
            $this->addError('texto', __('jota.misiones.error_sin_patrulla'));

            return;
        } catch (MisionCerrada) {
            $this->addError('texto', __('jota.misiones.error_vencida'));

            return;
        } catch (EntregaBloqueada $e) {
            $this->addError('texto', $e->estado->value === 'aprobada' ? __('jota.misiones.error_aprobada') : __('jota.misiones.error_pendiente'));

            return;
        } catch (AuthorizationException) {
            abort(403);
        }

        $this->enviada = true;
        $this->fotos = [];
        $this->texto = '';
    }

    /** Los mensajes de dominio ya están en español; los más comunes se reemplazan por el copy aprobado. */
    private function mensaje(EntregaInvalida $e): string
    {
        return match (true) {
            str_starts_with($e->getMessage(), 'Escribí algo') => __('jota.misiones.error_vacia'),
            str_starts_with($e->getMessage(), 'El texto puede') => __('jota.misiones.error_texto_largo'),
            str_starts_with($e->getMessage(), 'Podés subir') => __('jota.misiones.error_muchas_fotos'),
            str_starts_with($e->getMessage(), 'Cada imagen') => __('jota.misiones.error_foto_pesada'),
            str_starts_with($e->getMessage(), 'Solo se aceptan') => __('jota.misiones.error_foto_formato'),
            default => $e->getMessage(),
        };
    }

    public function render()
    {
        $mision = Mision::query()->findOrFail($this->misionId);

        return view('livewire.beneficiario.entregar', [
            'mision' => $mision,
            'vistas' => collect($this->fotos)->map(fn ($f) => $this->vista($f))->all(),
        ])->title(__('jota.misiones.entrega_titulo'));
    }

    private function vista($foto): ?string
    {
        try {
            return $foto->temporaryUrl();
        } catch (\Throwable) {
            return null;
        }
    }
}
