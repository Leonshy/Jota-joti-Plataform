<?php

namespace App\Http\Controllers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;

/**
 * Vista previa del sistema de diseño con datos de ejemplo. Solo se registra fuera de producción
 * (ver routes/web.php). No toca la base de datos ni el dominio.
 */
class DisenoController extends Controller
{
    /** Pantallas disponibles. La clave es la URL; el valor, la vista. */
    public const PANTALLAS = [
        'ingresar', 'activar', 'inicio', 'misiones', 'mision', 'entregar', 'actividades', 'escanear',
        'confirmar-pago', 'pago-ok', 'asistencia-ok', 'ranking', 'historial',
        'd-cola', 'd-revisar', 'd-qr', 'd-asistencia', 'd-cumplimientos', 'd-penalizaciones', 'd-ranking',
        't-cobrar', 't-qr', 't-devolver', 't-historial',
    ];

    public function guia(): View
    {
        return view('diseno.guia', ['pantallas' => self::PANTALLAS, 'qr' => $this->qr('https://jotajoti.example/demo')]);
    }

    public function pantalla(Request $request, string $pantalla): View
    {
        abort_unless(in_array($pantalla, self::PANTALLAS, true), 404);

        return view('diseno.pantallas.'.$pantalla, [
            'estado' => $request->query('estado'),
            'qr' => $this->qr('https://jotajoti.example/asistencia/demo/'.now()->format('Hi')),
        ]);
    }

    private function qr(string $contenido): HtmlString
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(480, 0), new SvgImageBackEnd));

        return new HtmlString($writer->writeString($contenido));
    }
}
