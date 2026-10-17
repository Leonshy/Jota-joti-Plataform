<?php

namespace App\Domain\Contactos;

use App\Enums\TipoContacto;
use App\Models\Ajuste;
use App\Models\User;

/**
 * Cuántos puntos vale un contacto, que define el admin (y por eso no está en el código): JOTA y JOTI, cada uno local
 * (Paraguay) o del exterior. Sin definir devuelve null y no se puede aprobar (mejor que acreditar cero sin que nadie se entere).
 */
class Puntaje
{
    /** @return array<string, string> clave => etiqueta */
    public static function claves(): array
    {
        return [
            'jota_local' => 'JOTA · contacto local (Paraguay)',
            'jota_exterior' => 'JOTA · contacto del exterior',
            'joti_local' => 'JOTI · contacto local (Paraguay)',
            'joti_exterior' => 'JOTI · contacto del exterior',
        ];
    }

    public static function clave(TipoContacto $tipo, bool $local): string
    {
        return $tipo->value.'_'.($local ? 'local' : 'exterior');
    }

    public function para(TipoContacto $tipo, bool $local): ?int
    {
        return $this->todos()[self::clave($tipo, $local)];
    }

    /** @return array<string, int|null> */
    public function todos(): array
    {
        $guardados = Ajuste::query()->whereIn('clave', array_map(fn ($k) => "contacto_{$k}", array_keys(self::claves())))->pluck('valor', 'clave');

        return collect(self::claves())
            ->mapWithKeys(fn ($_, $k) => [$k => $guardados->has("contacto_{$k}") ? (int) $guardados["contacto_{$k}"] : null])
            ->all();
    }

    /**
     * @param  array<string, int|string|null>  $valores  clave => puntos (0 o más)
     *
     * @throws ContactoInvalido
     */
    public function guardar(array $valores, User $admin): void
    {
        $antes = $this->todos();

        foreach (array_keys(self::claves()) as $clave) {
            $valor = $valores[$clave] ?? null;

            if ($valor === null || $valor === '' || ! is_numeric($valor) || (int) $valor < 0 || (int) $valor > 100000 || (string) (int) $valor !== (string) $valor) {
                throw new ContactoInvalido('Los puntajes son números enteros entre 0 y 100.000.');
            }
        }

        foreach (array_keys(self::claves()) as $clave) {
            Ajuste::query()->updateOrCreate(['clave' => "contacto_{$clave}"], ['valor' => (string) (int) $valores[$clave]]);
        }

        $despues = $this->todos();

        if ($antes !== $despues) {
            activity('configuracion')->causedBy($admin)->withProperties(['antes' => $antes, 'despues' => $despues])->log('Cambió el puntaje de los contactos');
        }
    }
}
