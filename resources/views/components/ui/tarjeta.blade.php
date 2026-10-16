@props(['tono' => 'base', 'relleno' => 'p-4'])

@php
    $tonos = [
        'base' => 'bg-superficie shadow-tarjeta',
        'suave' => 'bg-fondo-suave',
        'info' => 'bg-info-fondo',
        'recompensa' => 'bg-recompensa-fondo',
        'cian' => 'bg-info text-violeta-900',
        'rosa' => 'bg-recompensa text-violeta-900',
        'exito' => 'bg-exito-fondo',
        'aviso' => 'bg-aviso-fondo',
        'error' => 'bg-error-fondo',
        'marca' => 'bg-primario text-sobre-primario',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-md '.$relleno.' '.($tonos[$tono] ?? $tonos['base'])]) }}>{{ $slot }}</div>
