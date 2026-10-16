<?php

namespace App\Jobs;

use App\Domain\Misiones\ImagenEntrega;
use App\Models\EntregaImagen;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Re-codifica la imagen subida: decodifica el original, lo achica a 1600 px como máximo y lo
 * escribe de nuevo como JPEG. Al re-codificar no se copian metadatos, así que se pierde el EXIF
 * (incluida la ubicación GPS de fotos de menores). El original se borra.
 */
class ProcesarImagenEntrega implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $imagenId) {}

    public function handle(): void
    {
        $imagen = EntregaImagen::find($this->imagenId);

        if (! $imagen || $imagen->procesada_at) {
            return;
        }

        $disco = Storage::disk('private');

        $jpeg = ImageManager::gd()
            ->read($disco->get($imagen->ruta)) // aplica la orientación EXIF antes de descartarla
            ->scaleDown(ImagenEntrega::LADO_MAX, ImagenEntrega::LADO_MAX)
            ->toJpeg(82)
            ->toString();

        $final = "entregas/{$imagen->entrega_id}/".Str::uuid().'.jpg';
        $disco->put($final, $jpeg);
        $disco->delete($imagen->ruta);

        $imagen->update(['ruta' => $final, 'procesada_at' => now()]);
    }

    /** Agotados los reintentos: el original (con EXIF) no queda en disco. Nunca se sirve sin procesar. */
    public function failed(Throwable $e): void
    {
        $imagen = EntregaImagen::find($this->imagenId);

        if ($imagen && ! $imagen->procesada_at) {
            Storage::disk('private')->delete($imagen->ruta);
        }
    }
}
