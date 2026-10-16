<?php

namespace App\Http\Controllers;

use App\Domain\Actividades\AsistenciaYaRegistrada;
use App\Domain\Actividades\RegistrarAsistencia;
use App\Domain\Actividades\SesionCerrada;
use App\Domain\Actividades\TokenInvalido;
use App\Models\SesionAsistencia;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Destino del QR: registra la asistencia de quien escanea, ya logueado. */
class RegistrarAsistenciaController extends Controller
{
    public function __invoke(Request $request, SesionAsistencia $sesion, string $token, RegistrarAsistencia $registrar): JsonResponse|Response
    {
        $actividad = null;

        try {
            $asistencia = $registrar($request->user(), $sesion, $token);
            $actividad = $asistencia->actividad;
            $resultado = ['ok', '¡Listo! Registramos tu asistencia.', 200, $actividad->puntos];
        } catch (AsistenciaYaRegistrada $e) {
            $actividad = $sesion->actividad;
            $resultado = ['ya_registrado', $e->getMessage(), 200, 0];
        } catch (SesionCerrada $e) {
            $resultado = ['cerrada', $e->getMessage(), 422, 0];
        } catch (TokenInvalido $e) {
            $resultado = ['vencido', $e->getMessage(), 422, 0];
        } catch (AuthorizationException) {
            $resultado = ['no_permitido', 'Esta actividad no es para tu unidad.', 403, 0];
        }

        [$estado, $mensaje, $codigo, $puntos] = $resultado;

        if ($request->expectsJson()) {
            return response()->json(['estado' => $estado, 'mensaje' => $mensaje, 'puntos' => $puntos], $codigo);
        }

        // Llegó desde el escáner de la app o desde la cámara del sistema: se muestra el resultado con el diseño de la app.
        $usuario = $request->user()->fresh();

        return response()->view('asistencia.resultado', [
            'estado' => $estado,
            'actividad' => $actividad?->titulo,
            'puntos' => $puntos,
            'saldoPuntos' => $usuario->puntos,
            'saldoJlis' => $usuario->jlis,
        ], $codigo);
    }
}
