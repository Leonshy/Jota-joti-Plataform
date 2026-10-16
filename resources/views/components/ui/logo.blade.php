@props(['ancho' => 220])

<img src="{{ asset('brand/jotajoti-2026.svg') }}" alt="{{ __('jota.app.logo_alt') }}" width="{{ $ancho }}" height="{{ round($ancho * 203 / 1000) }}"
    {{ $attributes->merge(['class' => 'h-auto']) }}>
