<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Encabezados de seguridad de toda respuesta web (Fase 9).
 *
 * La política de contenido (CSP) deja cargar scripts, estilos, fuentes e imágenes solo de este mismo sitio y
 * prohíbe incrustar la plataforma en otra página, enviar formularios a otros sitios o conectarse a otros
 * servidores. Livewire, Alpine y Filament necesitan scripts en línea y evaluación dinámica, por eso
 * `script-src` los admite; aun así nada se puede cargar ni enviar fuera del sitio.
 */
class EncabezadosDeSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $politica = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "media-src 'self' blob:",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        $encabezados = [
            'Content-Security-Policy' => $politica,
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // La cámara se usa solo desde este sitio (escáner de QR); el resto de sensores, nunca.
            'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=(), payment=(), usb=(), accelerometer=(), gyroscope=(), magnetometer=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            // La plataforma es privada del evento: que los buscadores no la indexen.
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ];

        // Con HTTPS, el navegador no vuelve a abrir el sitio por HTTP (6 meses; sin subdominios).
        if ($request->isSecure()) {
            $encabezados['Strict-Transport-Security'] = 'max-age=15552000';
        }

        // No se anuncia la versión de PHP (ayuda a quien busca fallos conocidos).
        $respuesta->headers->remove('X-Powered-By');
        header_remove('X-Powered-By');

        foreach ($encabezados as $nombre => $valor) {
            $respuesta->headers->set($nombre, $valor);
        }

        return $respuesta;
    }
}
