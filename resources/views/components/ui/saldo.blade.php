@props(['etiqueta', 'valor', 'icono' => 'star', 'contar' => false, 'desde' => 0, 'tono' => 'blanco', 'ayuda' => null])

@php
    $bloque = match ($tono) {
        'cian' => 'bg-info text-violeta-900',
        'rosa' => 'bg-recompensa text-violeta-900',
        default => 'bg-superficie shadow-tarjeta',
    };
    $colorApoyo = $tono === 'blanco' ? 'text-texto-suave' : 'text-violeta-900';
    $colorNumero = $tono === 'blanco' ? 'text-titulo' : 'text-violeta-900';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-md p-4 '.$bloque]) }}>
    <p class="flex items-center gap-1.5 text-sm {{ $colorApoyo }}">
        <x-dynamic-component :component="icono($icono)" class="size-5 shrink-0 {{ $tono === 'blanco' ? 'text-primario' : '' }}" aria-hidden="true" />
        {{ $etiqueta }}
    </p>
    <p class="numeros mt-1 text-3xl font-bold {{ $colorNumero }}" @if ($contar) data-contar data-desde="{{ $desde }}" data-hasta="{{ $valor }}" @endif>{{ number_format($valor, 0, ',', '.') }}</p>
    @if ($ayuda)<p class="mt-0.5 text-xs {{ $colorApoyo }}">{{ $ayuda }}</p>@endif
</div>
