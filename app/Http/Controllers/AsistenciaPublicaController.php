<?php

namespace App\Http\Controllers;

use App\Domain\Actividades\QrAsistencia;
use App\Domain\Actividades\TokenRotativo;
use App\Models\SesionAsistencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Pantalla pública (sin login) que el dirigente proyecta: muestra un QR que cambia cada 60 s.
 * El enlace va firmado y deja de funcionar apenas se cierra la sesión.
 */
class AsistenciaPublicaController extends Controller
{
    public function show(SesionAsistencia $sesion): View
    {
        $this->exigirAbierta($sesion);

        return view('asistencia.publica', [
            'actividad' => $sesion->actividad->titulo,
            'urlQr' => URL::signedRoute('asistencia.qr', ['sesion' => $sesion]),
        ]);
    }

    public function qr(SesionAsistencia $sesion, QrAsistencia $qr, TokenRotativo $tokens): JsonResponse
    {
        $this->exigirAbierta($sesion);

        return response()->json([
            'svg' => $qr->svg($sesion),
            'segundos' => $tokens->segundosRestantes(),
        ])->header('Cache-Control', 'no-store');
    }

    private function exigirAbierta(SesionAsistencia $sesion): void
    {
        abort_unless($sesion->estaAbierta(), 410, 'La sesión de asistencia ya se cerró.');
    }
}
