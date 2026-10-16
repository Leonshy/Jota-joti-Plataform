@props(['activa' => null, 'modo' => 'diseno'])

@php($a = fn (string $clave) => $modo === 'app' ? route('dirigente.'.$clave) : url('/diseno/d-'.$clave))

<x-ui.barra-inferior :activa="$activa" :items="[
    ['clave' => 'cola', 'etiqueta' => __('jota.navegacion.cola'), 'href' => $modo === 'app' ? route('dirigente.inicio') : url('/diseno/d-cola'), 'icono' => 'inbox-stack'],
    ['clave' => 'asistencia', 'etiqueta' => __('jota.navegacion.asistencia'), 'href' => $a('asistencia'), 'icono' => 'qr-code'],
    ['clave' => 'cumplimientos', 'etiqueta' => __('jota.navegacion.cumplimientos'), 'href' => $a('cumplimientos'), 'icono' => 'check-badge'],
    ['clave' => 'penalizaciones', 'etiqueta' => __('jota.navegacion.penalizaciones'), 'href' => $a('penalizaciones'), 'icono' => 'exclamation-triangle'],
]" />
