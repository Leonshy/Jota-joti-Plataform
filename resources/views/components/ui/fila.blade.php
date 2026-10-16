@props(['titulo', 'subtitulo' => null, 'icono' => null, 'href' => null])

<li>
    @if ($href)<a href="{{ $href }}" class="flex min-h-16 items-center gap-3 px-4 py-3 text-texto no-underline transition duration-150 hover:bg-fondo-suave active:bg-primario-suave">@else<div class="flex min-h-16 items-center gap-3 px-4 py-3">@endif
        @if ($icono)
            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primario-suave text-primario">
                <x-dynamic-component :component="icono($icono)" class="size-6" aria-hidden="true" />
            </span>
        @endif
        <span class="min-w-0 flex-1">
            <span class="block break-words font-bold leading-snug">{{ $titulo }}</span>
            @if ($subtitulo)<span class="block text-sm text-texto-suave">{{ $subtitulo }}</span>@endif
        </span>
        @isset($derecha)<span class="shrink-0">{{ $derecha }}</span>@endisset
        @if ($href)<x-heroicon-o-chevron-right class="size-5 shrink-0 text-texto-suave" aria-hidden="true" />@endif
    @if ($href)</a>@else</div>@endif
</li>
