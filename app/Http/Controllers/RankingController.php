<?php

namespace App\Http\Controllers;

use App\Domain\Ranking\RankingUnidad;
use App\Enums\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Destino del polling del ranking. Devuelve solo lo que define RankingUnidad para cada rol
 * (posición y puntaje, sin identidades). Las pantallas llegan con el diseño (Fase 2).
 */
class RankingController extends Controller
{
    public function __invoke(Request $request, RankingUnidad $ranking): JsonResponse
    {
        $usuario = $request->user();

        $datos = match ($usuario->rol) {
            Rol::Beneficiario => $ranking->vistaBeneficiario($usuario),
            Rol::Dirigente => $ranking->vistaDirigente($usuario),
            default => abort(403),
        };

        return response()->json($datos)->header('Cache-Control', 'private, max-age=15');
    }
}
