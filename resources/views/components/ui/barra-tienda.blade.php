@props(['activa' => null, 'modo' => 'diseno'])

@php($a = fn (string $clave) => $modo === 'app' ? route('tienda.'.$clave) : url('/diseno/t-'.$clave))

<x-ui.barra-inferior :activa="$activa" :items="[
    ['clave' => 'cobrar', 'etiqueta' => __('jota.navegacion.cobrar'), 'href' => $modo === 'app' ? route('tienda.inicio') : url('/diseno/t-cobrar'), 'icono' => 'jota-moneda'],
    ['clave' => 'devolver', 'etiqueta' => __('jota.navegacion.devolver'), 'href' => $a('devolver'), 'icono' => 'arrow-uturn-left'],
    ['clave' => 'historial', 'etiqueta' => __('jota.navegacion.historial'), 'href' => $a('historial'), 'icono' => 'clock'],
]" />
