@props(['tono' => 'info', 'titulo' => null])

@php
    $tonos = [
        'info' => ['bg-info-fondo text-gris-900', 'information-circle', 'text-violeta-600'],
        'exito' => ['bg-exito-fondo text-gris-900', 'check-circle', 'text-exito-texto'],
        'aviso' => ['bg-aviso-fondo text-gris-900', 'exclamation-triangle', 'text-aviso-texto'],
        'error' => ['bg-error-fondo text-gris-900', 'exclamation-circle', 'text-error-texto'],
    ];
    [$clases, $ico, $colorIcono] = $tonos[$tono] ?? $tonos['info'];
@endphp

<div role="{{ in_array($tono, ['aviso', 'error']) ? 'alert' : 'status' }}" {{ $attributes->merge(['class' => 'flex gap-3 rounded-md p-4 '.$clases]) }}>
    <x-dynamic-component :component="'heroicon-o-'.$ico" class="mt-0.5 size-6 shrink-0 {{ $colorIcono }}" aria-hidden="true" />
    <div class="min-w-0 text-sm">
        @if ($titulo)<p class="font-bold">{{ $titulo }}</p>@endif
        <div>{{ $slot }}</div>
    </div>
</div>
