<?php

namespace App\Domain\Misiones;

use App\Enums\EstadoEntrega;
use App\Jobs\ProcesarImagenEntrega;
use App\Models\Entrega;
use App\Models\EntregaImagen;
use App\Models\Mision;
use App\Models\Patrulla;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EntregarMision
{
    public const MAX_TEXTO = 2000;

    /**
     * @param  list<UploadedFile>  $imagenes
     *
     * @throws AuthorizationException misión que no es de su unidad o aún no lanzada
     * @throws EntregaInvalida
     * @throws SinPatrulla
     * @throws MisionCerrada
     * @throws EntregaBloqueada
     */
    public function __invoke(User $user, Mision $mision, ?string $texto = null, array $imagenes = []): Entrega
    {
        Gate::forUser($user)->authorize('entregar', $mision);

        $texto = trim((string) $texto) ?: null;
        $this->validar($texto, $imagenes);

        if ($mision->esPorPatrulla() && ! $user->patrulla_id) {
            throw new SinPatrulla;
        }

        $guardados = [];

        try {
            $entrega = DB::transaction(function () use ($user, $mision, $texto, $imagenes, &$guardados) {
                // Se bloquea al sujeto (la persona o la patrulla) para que dos entregas
                // simultáneas no pasen ambas la regla de "una pendiente a la vez".
                if ($mision->esPorPatrulla()) {
                    Patrulla::query()->whereKey($user->patrulla_id)->lockForUpdate()->firstOrFail();
                } else {
                    User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
                }

                $actual = Mision::query()->findOrFail($mision->getKey());

                if (! $actual->estaAbierta()) {
                    throw new MisionCerrada($actual->cerrada_at ? 'cerrada' : 'vencida');
                }

                $previa = Entrega::query()
                    ->where('mision_id', $actual->getKey())
                    ->when(
                        $actual->esPorPatrulla(),
                        fn ($q) => $q->where('patrulla_id', $user->patrulla_id),
                        fn ($q) => $q->where('user_id', $user->getKey()),
                    )
                    ->whereIn('estado', [EstadoEntrega::Pendiente, EstadoEntrega::Aprobada])
                    ->first();

                if ($previa) {
                    throw new EntregaBloqueada($previa->estado);
                }

                $entrega = Entrega::create([
                    'mision_id' => $actual->getKey(),
                    'user_id' => $user->getKey(),
                    'patrulla_id' => $actual->esPorPatrulla() ? $user->patrulla_id : null,
                    'texto' => $texto,
                    'estado' => EstadoEntrega::Pendiente,
                ]);

                foreach ($imagenes as $archivo) {
                    $ruta = Storage::disk('private')->putFile('tmp', $archivo);
                    $guardados[] = $ruta;
                    $entrega->imagenes()->create(['ruta' => $ruta]);
                }

                return $entrega;
            });
        } catch (Throwable $e) {
            Storage::disk('private')->delete($guardados);

            throw $e;
        }

        $entrega->imagenes->each(fn (EntregaImagen $i) => ProcesarImagenEntrega::dispatch($i->getKey()));

        return $entrega;
    }

    /** @param  list<UploadedFile>  $imagenes */
    private function validar(?string $texto, array $imagenes): void
    {
        if ($texto === null && $imagenes === []) {
            throw new EntregaInvalida('Escribí algo o subí al menos una foto.');
        }

        if ($texto !== null && mb_strlen($texto) > self::MAX_TEXTO) {
            throw new EntregaInvalida('El texto puede tener hasta '.self::MAX_TEXTO.' caracteres.');
        }

        if (count($imagenes) > ImagenEntrega::MAX_POR_ENTREGA) {
            throw new EntregaInvalida('Podés subir hasta '.ImagenEntrega::MAX_POR_ENTREGA.' fotos.');
        }

        foreach ($imagenes as $archivo) {
            ImagenEntrega::validar($archivo);
        }
    }
}
