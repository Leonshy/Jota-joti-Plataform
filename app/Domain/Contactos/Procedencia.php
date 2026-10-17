<?php

namespace App\Domain\Contactos;

use Illuminate\Support\Str;

/** Si un contacto es local (de Paraguay) o del exterior. En JOTI se sabe por el país; en JOTA, por el indicativo (ZP…). */
class Procedencia
{
    public static function esPaisLocal(string $pais): bool
    {
        return in_array(self::normalizar($pais), ['paraguay', 'py'], true);
    }

    /** Los indicativos de radioaficionados de Paraguay empiezan con ZP (ZP5ZDM, ZP6ASP…). */
    public static function esIndicativoLocal(string $indicativo): bool
    {
        return str_starts_with(self::indicativo($indicativo), 'ZP');
    }

    public static function indicativo(string $indicativo): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($indicativo)));
    }

    private static function normalizar(string $texto): string
    {
        return preg_replace('/\s+/', ' ', Str::ascii(mb_strtolower(trim($texto))));
    }
}
