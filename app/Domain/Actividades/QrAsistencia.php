<?php

namespace App\Domain\Actividades;

use App\Models\SesionAsistencia;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** Dibuja el QR vigente de una sesión: una URL de la app con el token de la ventana actual. */
class QrAsistencia
{
    public function __construct(private TokenRotativo $tokens) {}

    public function url(SesionAsistencia $sesion): string
    {
        return route('asistencia.registrar', ['sesion' => $sesion, 'token' => $this->tokens->generar($sesion)]);
    }

    public function svg(SesionAsistencia $sesion): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(480, 2), new SvgImageBackEnd));

        return $writer->writeString($this->url($sesion));
    }
}
