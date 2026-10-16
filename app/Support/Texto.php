<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\HtmlString;

/** Pequeñas utilidades de texto para el copy en español de la interfaz. */
class Texto
{
    /** «1 punto», «10 puntos», «−5 puntos»: concordancia por unidad (ver `jota.unidad.*`). */
    public static function cantidad(int $n, string $unidad): string
    {
        return number_format($n, 0, ',', '.').' '.trans_choice('jota.unidad.'.$unidad, abs($n));
    }

    /** «hoy 15:00», «mañana 09:30», «sáb 17 oct, 15:00»: cuándo es algo, según la hora del servidor. */
    public static function cuando(CarbonInterface $momento): string
    {
        $hora = $momento->format('H:i');

        return match (true) {
            $momento->isToday() => 'hoy '.$hora,
            $momento->isTomorrow() => 'mañana '.$hora,
            default => $momento->copy()->locale('es')->isoFormat('ddd D MMM').', '.$hora,
        };
    }

    /**
     * Traduce una clave y da énfasis (negrita violeta) a lo que va entre **dobles asteriscos**, como
     * el sitio oficial resalta las palabras clave dentro de una frase gris.
     *
     * Seguro frente a XSS: la plantilla del copy y todos los datos se escapan; el énfasis solo lo
     * decide el copy, nunca un dato (un nombre con ** no genera negrita).
     *
     * @param  array<string, scalar|\Stringable>  $datos
     */
    public static function copia(string $clave, array $datos = []): HtmlString
    {
        $html = preg_replace(
            '/\*\*(.+?)\*\*/s',
            '<strong class="font-bold text-titulo">$1</strong>',
            e(trans($clave)),
        );

        uksort($datos, fn ($a, $b) => strlen($b) <=> strlen($a)); // ":puntos" antes que ":punto"

        foreach ($datos as $nombre => $valor) {
            $html = str_replace(':'.$nombre, e((string) $valor), $html);
        }

        return new HtmlString($html);
    }
}
