<?php

namespace App\Domain\Misiones;

use finfo;
use Illuminate\Http\UploadedFile;

/** Validación por contenido real (no por extensión ni por el MIME que declara el cliente). */
class ImagenEntrega
{
    public const MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public const MAX_BYTES = 8 * 1024 * 1024;

    public const MAX_POR_ENTREGA = 4;

    public const LADO_MAX = 1600;

    /** Tope de píxeles al decodificar: evita bombas de descompresión en la cola. */
    private const MAX_PIXELES = 40_000_000;

    /** @throws EntregaInvalida */
    public static function validar(UploadedFile $archivo): void
    {
        $ruta = $archivo->getRealPath();

        if (! $archivo->isValid() || $ruta === false) {
            throw new EntregaInvalida('No se pudo subir una de las imágenes. Probá de nuevo.');
        }

        if ($archivo->getSize() > self::MAX_BYTES) {
            throw new EntregaInvalida('Cada imagen puede pesar hasta 8 MB.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
        $medidas = $mime && in_array($mime, self::MIMES, true) ? @getimagesize($ruta) : false;

        if ($medidas === false) {
            throw new EntregaInvalida('Solo se aceptan fotos JPG, PNG o WebP.');
        }

        if ($medidas[0] * $medidas[1] > self::MAX_PIXELES) {
            throw new EntregaInvalida('Una de las imágenes es demasiado grande.');
        }
    }
}
