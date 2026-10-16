@props(['titulo' => null, 'volver' => null, 'barra' => null, 'completa' => false, 'h1Propio' => false])

{{-- Columna móvil: ocupa el ancho del celular y se centra en pantallas grandes. --}}
<div {{ $attributes->merge(['class' => 'relative mx-auto flex min-h-dvh w-full max-w-md flex-col bg-fondo md:shadow-elevada']) }}>
    @if ($titulo || $volver)
        <header class="sticky top-0 z-10 flex min-h-16 items-center gap-1 border-b border-borde bg-fondo px-2 pt-[env(safe-area-inset-top)]">
            @if ($volver)
                <a href="{{ $volver }}" aria-label="{{ __('jota.comun.volver') }}" class="grid size-tactil shrink-0 place-items-center rounded-full text-primario transition duration-150 hover:bg-primario-suave active:bg-primario-suave">
                    <x-heroicon-o-arrow-left class="size-6" aria-hidden="true" />
                </a>
            @endif
            @if ($titulo)<p class="truncate px-2 text-lg font-bold text-titulo">{{ $titulo }}</p>@endif
            @isset($acciones)<div class="ml-auto flex items-center">{{ $acciones }}</div>@endisset
        </header>
    @endif

    <main id="contenido" tabindex="-1" @class(['flex flex-1 flex-col gap-5 px-4 py-5 focus:outline-none', 'pb-32' => $barra, 'items-center justify-center' => $completa])>
        {{-- Cada pantalla tiene exactamente un h1: el de la página (h1-propio) o, si no, el título de la cabecera, oculto a la vista. --}}
        @if ($titulo && ! $h1Propio)<h1 class="sr-only">{{ $titulo }}</h1>@endif
        {{ $slot }}
    </main>

    {{ $barra }}
</div>
