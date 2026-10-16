<?php

namespace App\Domain\Participantes;

use App\Enums\ModoRanking;
use App\Enums\Rol;
use App\Models\Patrulla;
use App\Models\Unidad;

/**
 * Reglas de estructura de un participante, compartidas por el formulario del panel y por la
 * importación CSV (una sola fuente de verdad). Datos personales mínimos (CLAUDE.md §2.12).
 */
class ReglasParticipante
{
    /** CI sin puntos ni espacios: "1.234.567" → "1234567". */
    public static function normalizarCi(string $ci): string
    {
        return preg_replace('/[\s.\-]/', '', trim($ci));
    }

    /**
     * @param  array{ci?: string|null, nombre?: string|null, rol?: Rol|null, grupo_id?: int|null, unidad_id?: int|null, patrulla_id?: int|null, patrulla_nueva?: bool}  $d
     *                                                                                                                                                                      `patrulla_nueva` indica que la patrulla todavía no existe pero se va a crear en la unidad y grupo dados.
     * @return array<string, string> errores por campo (vacío si está todo bien)
     */
    public static function validar(array $d): array
    {
        $errores = [];
        $rol = $d['rol'] ?? Rol::Beneficiario;
        $patrullaNueva = (bool) ($d['patrulla_nueva'] ?? false);
        $tienePatrulla = ! empty($d['patrulla_id']) || $patrullaNueva;

        if (! preg_match('/^\d{4,12}$/', (string) ($d['ci'] ?? ''))) {
            $errores['ci'] = 'El CI debe tener solo números (entre 4 y 12 dígitos).';
        }

        $nombre = trim((string) ($d['nombre'] ?? ''));
        if ($nombre === '') {
            $errores['nombre'] = 'Falta el nombre.';
        } elseif (mb_strlen($nombre) > 255) {
            $errores['nombre'] = 'El nombre es demasiado largo.';
        }

        $unidad = ! empty($d['unidad_id']) ? Unidad::query()->find($d['unidad_id']) : null;

        if ($rol === Rol::Beneficiario && empty($d['grupo_id'])) {
            $errores['grupo'] = 'Falta el grupo.';
        }

        if (in_array($rol, [Rol::Beneficiario, Rol::Dirigente], true) && ! $unidad) {
            $errores['unidad'] = 'Falta la unidad.';
        }

        if ($rol !== Rol::Beneficiario) {
            if ($tienePatrulla) {
                $errores['patrulla'] = 'Solo los participantes tienen patrulla.';
            }

            return $errores;
        }

        if (! $unidad) {
            return $errores;
        }

        if ($unidad->modo_ranking === ModoRanking::Individual) {
            if ($tienePatrulla) {
                $errores['patrulla'] = "{$unidad->nombre} no se arma por patrulla: dejá la patrulla vacía.";
            }

            return $errores;
        }

        if (! $tienePatrulla) {
            $errores['patrulla'] = "En {$unidad->nombre} cada participante va en una patrulla.";
        } elseif (! empty($d['patrulla_id'])) {
            $patrulla = Patrulla::query()->find($d['patrulla_id']);

            if (! $patrulla || $patrulla->unidad_id !== $unidad->getKey()) {
                $errores['patrulla'] = "Esa patrulla no es de {$unidad->nombre}.";
            } elseif (! empty($d['grupo_id']) && $patrulla->grupo_id !== (int) $d['grupo_id']) {
                $errores['patrulla'] = 'Esa patrulla es de otro grupo.';
            }
        }

        return $errores;
    }
}
