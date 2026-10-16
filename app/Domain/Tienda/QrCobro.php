<?php

namespace App\Domain\Tienda;

use App\Models\Cobro;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** El QR de cobro codifica una URL de la app; el token va en la ruta. */
class QrCobro
{
    public function url(Cobro $cobro): string
    {
        return route('cobro.ver', ['token' => $cobro->token]);
    }

    public function svg(Cobro $cobro): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(480, 2), new SvgImageBackEnd));

        return $writer->writeString($this->url($cobro));
    }
}
