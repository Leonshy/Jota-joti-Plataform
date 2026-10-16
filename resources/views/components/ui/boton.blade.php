@props([
    'variante' => 'primario',   // primario | secundario | peligro | enlace
    'href' => null,
    'type' => 'button',
    'icono' => null,            // ícono antes del texto (nombre de Heroicon outline)
    'iconoFinal' => null,       // ícono después del texto
    'bloque' => true,
    'cargando' => false,
    'deshabilitado' => false,
])

@php
    $inactivo = $deshabilitado || $cargando;

    $forma = $variante === 'enlace'
        ? 'min-h-tactil rounded-sm px-2'
        : 'min-h-boton rounded-full px-6';

    $color = match (true) {
        $inactivo && $variante !== 'enlace' => 'bg-gris-100 text-texto-suave cursor-not-allowed',
        $variante === 'primario' => 'bg-primario text-sobre-primario hover:bg-primario-fuerte active:scale-[0.98]',
        $variante === 'secundario' => 'bg-superficie text-primario ring-2 ring-inset ring-primario hover:bg-primario-suave active:scale-[0.98]',
        $variante === 'peligro' => 'bg-error-texto text-sobre-primario hover:brightness-110 active:scale-[0.98]',
        default => 'text-enlace hover:underline',
    };

    $clases = 'inline-flex items-center justify-center gap-2 select-none text-center text-base font-bold transition duration-150 ease-salida motion-reduce:active:scale-100 '
        .$forma.' '.$color.($bloque && $variante !== 'enlace' ? ' w-full' : '');
@endphp

@if ($href && ! $inactivo)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>
        @if ($icono)<x-dynamic-component :component="icono($icono)" class="size-5 shrink-0" aria-hidden="true" />@endif
        <span>{{ $slot }}</span>
        @if ($iconoFinal)<x-dynamic-component :component="icono($iconoFinal)" class="size-5 shrink-0" aria-hidden="true" />@endif
    </a>
@else
    <button type="{{ $type }}" @disabled($inactivo) @if ($cargando) aria-busy="true" @endif {{ $attributes->merge(['class' => $clases]) }}>
        @if ($cargando)
            <x-heroicon-o-arrow-path class="size-5 shrink-0 animate-giro" aria-hidden="true" />
        @elseif ($icono)
            <x-dynamic-component :component="icono($icono)" class="size-5 shrink-0" aria-hidden="true" />
        @endif
        <span>{{ $slot }}</span>
        @if ($iconoFinal && ! $cargando)<x-dynamic-component :component="icono($iconoFinal)" class="size-5 shrink-0" aria-hidden="true" />@endif
    </button>
@endif
