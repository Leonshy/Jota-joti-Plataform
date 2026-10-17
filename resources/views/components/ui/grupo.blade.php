@props(['titulo', 'icono' => null])

{{-- Un bloque grande de la pantalla (p. ej. «Talleres» y «Estaciones JOTA»), con un encabezado que lo separa claramente del resto. --}}
@php($id = 'grupo-'.\Illuminate\Support\Str::slug($titulo))

<section aria-labelledby="{{ $id }}" {{ $attributes->merge(['class' => 'flex flex-col gap-4']) }}>
    <h2 id="{{ $id }}" class="flex items-center gap-2 border-b-2 border-primario pb-2 text-xl font-bold text-titulo">
        @if ($icono)<x-dynamic-component :component="icono($icono)" class="size-6 shrink-0 text-primario" aria-hidden="true" />@endif
        {{ $titulo }}
    </h2>
    {{ $slot }}
</section>
