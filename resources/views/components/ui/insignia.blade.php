@props(['tono' => 'neutro', 'icono' => true])

@php
    $tonos = [
        'neutro' => ['bg-gris-100 text-gris-900', null],
        'marca' => ['bg-primario-suave text-primario', null],
        'info' => ['bg-info-fondo text-violeta-900', null],
        'exito' => ['bg-exito-fondo text-exito-texto', 'check-circle'],
        'aviso' => ['bg-aviso-fondo text-aviso-texto', 'clock'],
        'error' => ['bg-error-fondo text-error-texto', 'x-circle'],
    ];
    [$clases, $ico] = $tonos[$tono] ?? $tonos['neutro'];
@endphp

{{-- El estado no depende solo del color: éxito, aviso y error llevan ícono además de texto. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold '.$clases]) }}>
    @if ($icono && $ico)<x-dynamic-component :component="'heroicon-m-'.$ico" class="size-4 shrink-0" aria-hidden="true" />@endif
    {{ $slot }}
</span>
