@props(['tono' => 'exito', 'titulo', 'texto' => null, 'animar' => true])

@php
    // Discos sólidos del color de marca, con un aro cian: el sello se ve del evento, no un tilde verde genérico.
    $tonos = [
        'exito' => 'bg-exito text-sobre-primario ring-8 ring-info',
        'aviso' => 'bg-aviso text-gris-900 ring-8 ring-aviso-fondo',
        'error' => 'bg-error-texto text-sobre-primario ring-8 ring-error-fondo',
        'info' => 'bg-info text-violeta-900 ring-8 ring-primario-suave',
    ];
    $iconos = ['aviso' => 'exclamation-triangle', 'error' => 'x-mark', 'info' => 'information-circle'];
@endphp

{{-- Pantalla de resultado: un círculo, un título, una frase y las acciones. El sello solo se anima en el éxito. --}}
<div {{ $attributes->merge(['class' => 'flex flex-1 flex-col items-center justify-center gap-4 px-2 py-8 text-center']) }}>
    <div class="relative grid size-28 place-items-center">
        @if ($animar && $tono === 'exito')
            <span class="absolute inset-0 rounded-full bg-info animate-onda" aria-hidden="true"></span>
        @endif
        <span class="relative grid size-24 place-items-center rounded-full {{ $tonos[$tono] ?? $tonos['exito'] }} {{ $animar ? 'animate-sello' : '' }}">
            @if ($tono === 'exito')
                <svg viewBox="0 0 24 24" class="size-12" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path class="trazo-check" pathLength="1" d="M5 12.5l4.5 4.5L19 7.5" />
                </svg>
            @else
                <x-dynamic-component :component="'heroicon-o-'.$iconos[$tono]" class="size-12" aria-hidden="true" />
            @endif
        </span>
    </div>

    <h1 class="text-2xl text-titulo {{ $animar ? 'animate-sube' : '' }}" style="animation-delay: 60ms">{{ $titulo }}</h1>
    @if ($texto)
        <p class="max-w-xs text-base text-texto-suave {{ $animar ? 'animate-sube' : '' }}" style="animation-delay: 120ms">{{ $texto }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="mt-2 flex w-full flex-col gap-3">{{ $slot }}</div>
    @endif
</div>
