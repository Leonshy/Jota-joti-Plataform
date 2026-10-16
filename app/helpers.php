<?php

use App\Domain\Acceso\UnidadActiva;
use App\Models\User;
use App\Support\Texto;
use Carbon\CarbonInterface;
use Illuminate\Support\HtmlString;

if (! function_exists('cantidad')) {
    /** «10 puntos», «1 LisCoin»: número con su unidad bien concordada. */
    function cantidad(int $n, string $unidad): string
    {
        return Texto::cantidad($n, $unidad);
    }
}

if (! function_exists('icono')) {
    /** Nombre del componente de un ícono: los nuestros llevan «jota-» (p. ej. jota-moneda); el resto son Heroicons outline. */
    function icono(string $nombre): string
    {
        return str_starts_with($nombre, 'jota-') ? $nombre : 'heroicon-o-'.$nombre;
    }
}

if (! function_exists('unidad_activa')) {
    /** Unidad sobre la que actúa el usuario en las pantallas de dirigente (la suya, o la elegida si es admin). */
    function unidad_activa(?User $user = null): ?int
    {
        return app(UnidadActiva::class)->para($user ?? auth()->user());
    }
}

if (! function_exists('cuando')) {
    /** «hoy 15:00», «mañana 09:30», «sáb 17 oct, 15:00». */
    function cuando(CarbonInterface $momento): string
    {
        return Texto::cuando($momento);
    }
}

if (! function_exists('copia')) {
    /** Copy traducido con **énfasis** violeta, escapado. */
    function copia(string $clave, array $datos = []): HtmlString
    {
        return Texto::copia($clave, $datos);
    }
}
