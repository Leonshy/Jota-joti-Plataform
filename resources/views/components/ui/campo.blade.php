@props([
    'name',
    'label',
    'type' => 'text',
    'valor' => null,
    'error' => null,
    'ayuda' => null,
    'area' => false,
    'pin' => false,
    'placeholder' => null,
    'filas' => 4,
])

@php
    $id = 'campo-'.$name;
    $describe = collect([$ayuda ? $id.'-ayuda' : null, $error ? $id.'-error' : null])->filter()->implode(' ');

    $base = 'block w-full rounded-sm border-2 bg-superficie px-4 text-lg text-texto transition duration-150 focus:border-primario ';
    $estado = $error ? 'border-error-texto bg-error-fondo' : 'border-borde-campo';
    $forma = $pin ? 'min-h-boton text-center text-2xl tracking-[0.6em]' : ($area ? 'min-h-32 py-3' : 'min-h-boton');
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-bold">{{ $label }}</label>

    @if ($area)
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $filas }}" placeholder="{{ $placeholder }}"
            @if ($describe) aria-describedby="{{ $describe }}" @endif @if ($error) aria-invalid="true" @endif
            {{ $attributes->merge(['class' => $base.$estado.' '.$forma]) }}>{{ $valor }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" value="{{ $valor }}" placeholder="{{ $placeholder }}"
            type="{{ $pin ? 'password' : $type }}"
            @if ($pin) inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="current-password" @endif
            @if ($describe) aria-describedby="{{ $describe }}" @endif @if ($error) aria-invalid="true" @endif
            {{ $attributes->merge(['class' => $base.$estado.' '.$forma]) }}>
    @endif

    @if ($ayuda)
        <p id="{{ $id }}-ayuda" class="mt-1.5 text-xs text-texto-suave">{{ $ayuda }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" role="alert" class="mt-1.5 flex items-start gap-1.5 text-sm font-bold text-error-texto">
            <x-heroicon-o-exclamation-circle class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
