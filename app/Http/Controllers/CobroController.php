<?php

namespace App\Http\Controllers;

use App\Domain\Ledger\SaldoInsuficiente;
use App\Domain\Tienda\CobroNoExiste;
use App\Domain\Tienda\CobroVencido;
use App\Domain\Tienda\CobroYaPagado;
use App\Domain\Tienda\PagarCobro;
use App\Models\Cobro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Lado del participante: el QR de cobro abre `ver` (muestra el monto) y la persona confirma con
 * `pagar`. Quien escanea con la app o con la cámara del sistema ve las pantallas; quien pide JSON (`Accept`) recibe JSON.
 */
class CobroController extends Controller
{
    public function ver(Request $request, string $token): JsonResponse|Response
    {
        Gate::authorize('pagar', Cobro::class);

        $cobro = Cobro::query()->with('creador:id,name')->where('token', $token)->first();
        $saldo = $request->user()->jlis;

        if (! $request->expectsJson()) {
            return $this->pantallaDeConfirmacion($cobro, $saldo);
        }

        if (! $cobro) {
            return response()->json(['estado' => 'no_existe', 'mensaje' => (new CobroNoExiste)->getMessage()], 404);
        }

        return response()->json([
            'estado' => $cobro->estado(),
            'monto' => $cobro->monto,
            'tienda' => $cobro->creador->name,
            'segundos_restantes' => $cobro->segundosRestantes(),
            'saldo' => $saldo,
            'alcanza' => $saldo >= $cobro->monto,
        ])->header('Cache-Control', 'no-store');
    }

    public function pagar(Request $request, string $token, PagarCobro $pagar): JsonResponse|Response
    {
        $usuario = $request->user();
        $web = ! $request->expectsJson();

        try {
            $cobro = $pagar($usuario, $token);
            $saldo = $usuario->fresh()->jlis;

            if ($web) {
                return response()->view('cobro.resultado', ['estado' => 'ok', 'monto' => $cobro->monto, 'tienda' => $cobro->creador()->value('name'), 'saldo' => $saldo, 'saldoAntes' => $saldo + $cobro->monto]);
            }

            return response()->json(['estado' => 'pagado', 'mensaje' => '¡Pago listo!', 'monto' => $cobro->monto, 'saldo' => $saldo]);
        } catch (CobroYaPagado $e) {
            return $web ? response()->view('cobro.resultado', ['estado' => 'ya_pagado']) : response()->json(['estado' => 'ya_pagado', 'mensaje' => $e->getMessage()]);
        } catch (CobroVencido|CobroNoExiste $e) {
            $estado = $e instanceof CobroVencido ? 'vencido' : 'no_existe';
            $codigo = $e instanceof CobroVencido ? 422 : 404;

            return $web ? response()->view('cobro.resultado', ['estado' => $estado], $codigo) : response()->json(['estado' => $estado, 'mensaje' => $e->getMessage()], $codigo);
        } catch (SaldoInsuficiente $e) {
            if ($web) {
                return response()->view('cobro.resultado', ['estado' => 'saldo', 'faltan' => $e->monto - $e->saldo, 'token' => $token], 422);
            }

            return response()->json([
                'estado' => 'saldo_insuficiente',
                'mensaje' => 'No te alcanzan los LisCoins para este cobro.',
                'saldo' => $e->saldo,
                'monto' => $e->monto,
                'faltan' => $e->monto - $e->saldo,
            ], 422);
        }
    }

    /** Pantalla que se abre al escanear el QR de cobro: el monto, la tienda, el saldo y el botón para pagar. */
    private function pantallaDeConfirmacion(?Cobro $cobro, int $saldo): Response
    {
        if (! $cobro) {
            return response()->view('cobro.resultado', ['estado' => 'no_existe'], 404);
        }

        return match ($cobro->estado()) {
            Cobro::PAGADO => response()->view('cobro.resultado', ['estado' => 'ya_pagado']),
            Cobro::VENCIDO => response()->view('cobro.resultado', ['estado' => 'vencido'], 422),
            default => response()->view('cobro.confirmar', [
                'cobro' => ['token' => $cobro->token, 'monto' => $cobro->monto, 'tienda' => $cobro->creador->name, 'segundos' => $cobro->segundosRestantes()],
                'saldo' => $saldo,
            ]),
        };
    }
}
