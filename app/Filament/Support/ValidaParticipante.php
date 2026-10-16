<?php

namespace App\Filament\Support;

use App\Domain\Participantes\ReglasParticipante;
use App\Enums\Rol;
use Illuminate\Validation\ValidationException;

/** Aplica las mismas reglas de estructura que la importación CSV al guardar desde el formulario. */
trait ValidaParticipante
{
    /** @param  array<string, mixed>  $data */
    protected function validarEstructura(array $data): array
    {
        $errores = ReglasParticipante::validar([
            'ci' => ReglasParticipante::normalizarCi((string) ($data['ci'] ?? '')),
            'nombre' => $data['name'] ?? '',
            'rol' => $data['rol'] instanceof Rol ? $data['rol'] : Rol::tryFrom((string) ($data['rol'] ?? '')),
            'grupo_id' => $data['grupo_id'] ?? null,
            'unidad_id' => $data['unidad_id'] ?? null,
            'patrulla_id' => $data['patrulla_id'] ?? null,
        ]);

        if ($errores !== []) {
            $campos = ['ci' => 'data.ci', 'nombre' => 'data.name', 'grupo' => 'data.grupo_id', 'unidad' => 'data.unidad_id', 'patrulla' => 'data.patrulla_id'];

            throw ValidationException::withMessages(
                collect($errores)->mapWithKeys(fn ($mensaje, $campo) => [$campos[$campo] ?? 'data.'.$campo => $mensaje])->all(),
            );
        }

        $data['ci'] = ReglasParticipante::normalizarCi((string) $data['ci']);

        // Solo los participantes tienen patrulla.
        if (($data['rol'] ?? null) !== Rol::Beneficiario && ($data['rol'] ?? null) !== Rol::Beneficiario->value) {
            $data['patrulla_id'] = null;
        }

        return $data;
    }
}
