@props(['activa' => null, 'modo' => 'diseno'])

@php($a = fn (string $clave) => $modo === 'app' ? route('app.'.$clave) : url('/diseno/'.$clave))

<x-ui.barra-inferior :activa="$activa" :items="[
    ['clave' => 'inicio', 'etiqueta' => __('jota.navegacion.inicio'), 'href' => $a('inicio'), 'icono' => 'home'],
    ['clave' => 'misiones', 'etiqueta' => __('jota.navegacion.misiones'), 'href' => $a('misiones'), 'icono' => 'flag'],
    ['clave' => 'escanear', 'etiqueta' => __('jota.navegacion.escanear'), 'href' => $a('escanear'), 'icono' => 'qr-code', 'centro' => true],
    ['clave' => 'actividades', 'etiqueta' => __('jota.navegacion.actividades'), 'href' => $a('actividades'), 'icono' => 'calendar-days'],
    ['clave' => 'ranking', 'etiqueta' => __('jota.navegacion.ranking'), 'href' => $a('ranking'), 'icono' => 'trophy'],
]" />
