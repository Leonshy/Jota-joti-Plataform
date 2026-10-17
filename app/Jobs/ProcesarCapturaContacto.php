<?php

namespace App\Jobs;

use App\Domain\Misiones\ImagenEntrega;
use App\Models\Contacto;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Re-codifica la captura de pantalla de un contacto JOTI igual que las fotos de las entregas: se achica a 1600 px y se
 * escribe de nuevo como JPEG, así se pierde el EXIF. El original se borra y nunca se sirve sin procesar.
 */
class ProcesarCapturaContacto implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $contactoId) {}

    public function handle(): void
    {
        $contacto = Contacto::find($this->contactoId);

        if (! $contacto || $contacto->captura_procesada_at || ! $contacto->captura_ruta) {
            return;
        }

        $disco = Storage::disk('private');

        $jpeg = ImageManager::gd()
            ->read($disco->get($contacto->captura_ruta))
            ->scaleDown(ImagenEntrega::LADO_MAX, ImagenEntrega::LADO_MAX)
            ->toJpeg(82)
            ->toString();

        $final = "contactos/{$contacto->getKey()}/".Str::uuid().'.jpg';
        $disco->put($final, $jpeg);
        $disco->delete($contacto->captura_ruta);

        $contacto->update(['captura_ruta' => $final, 'captura_procesada_at' => now()]);
    }

    public function failed(Throwable $e): void
    {
        $contacto = Contacto::find($this->contactoId);

        if ($contacto && ! $contacto->captura_procesada_at && $contacto->captura_ruta) {
            Storage::disk('private')->delete($contacto->captura_ruta);
        }
    }
}
