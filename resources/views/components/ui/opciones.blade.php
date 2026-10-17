@props(['name', 'label', 'opciones', 'vacio' => null, 'error' => null, 'ayuda' => null])

{{-- Desplegable de opciones, con el mismo aspecto y la misma accesibilidad que el campo de texto. --}}
@php
    $id = 'campo-'.$name;
    $describe = collect([$ayuda ? $id.'-ayuda' : null, $error ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-bold">{{ $label }}</label>
    <select id="{{ $id }}" name="{{ $name }}" @if ($describe) aria-describedby="{{ $describe }}" @endif @if ($error) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => 'block min-h-boton w-full rounded-sm border-2 bg-superficie px-4 text-lg text-texto transition duration-150 focus:border-primario '.($error ? 'border-error-texto bg-error-fondo' : 'border-borde-campo')]) }}>
        @if ($vacio)<option value="">{{ $vacio }}</option>@endif
        @foreach ($opciones as $valor => $texto)
            <option value="{{ $valor }}">{{ $texto }}</option>
        @endforeach
    </select>
    @if ($ayuda)<p id="{{ $id }}-ayuda" class="mt-1.5 text-xs text-texto-suave">{{ $ayuda }}</p>@endif
    @if ($error)
        <p id="{{ $id }}-error" role="alert" class="mt-1.5 flex items-start gap-1.5 text-sm font-bold text-error-texto">
            <x-heroicon-o-exclamation-circle class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
