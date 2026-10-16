@props(['n' => 1, 'src' => null, 'estado' => 'lista'])

@php($etiqueta = $estado === 'procesando' ? __('jota.misiones.foto_procesando') : __('jota.diseno.foto_ejemplo', ['n' => $n]))

<div class="relative aspect-[4/3] overflow-hidden rounded-sm bg-primario-suave ring-4 ring-inset ring-info">
    @if ($src)
        <img src="{{ $src }}" alt="{{ __('jota.misiones.foto_alt', ['n' => $n]) }}" loading="lazy" class="size-full object-cover">
    @else
        <div class="grid size-full place-items-center text-center text-sm text-primario">
            <span class="flex flex-col items-center gap-1">
                <x-heroicon-o-photo class="size-8" aria-hidden="true" />
                {{ $etiqueta }}
            </span>
        </div>
    @endif
</div>
