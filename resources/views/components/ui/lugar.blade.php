{{-- Dónde se hace una actividad o una misión. Sin lugar cargado no muestra nada. --}}
@if (filled($slot->toHtml()))
    <p {{ $attributes->class('flex items-start gap-1.5 text-sm text-texto-suave') }}>
        <x-dynamic-component :component="icono('map-pin')" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
        <span><span class="sr-only">{{ __('jota.comun.lugar') }}: </span>{{ $slot }}</span>
    </p>
@endif
