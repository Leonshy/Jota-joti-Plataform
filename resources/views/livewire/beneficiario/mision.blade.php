<x-ui.pantalla wire:poll.30s :volver="route('app.misiones')" :titulo="__('jota.misiones.titulo')" :h1-propio="true">
    @php($estado = $detalle['estado'])
    @php($entrega = $detalle['entrega'])
    <div class="flex flex-col gap-3">
        <h1 class="text-2xl text-titulo">{{ $mision->titulo }}</h1>
        <x-ui.lugar>{{ $mision->lugar }}</x-ui.lugar>
        <div class="flex flex-wrap gap-2">
            <x-ui.insignia tono="marca">{{ $mision->esPorPatrulla() ? __('jota.misiones.patrulla') : __('jota.misiones.individual') }}</x-ui.insignia>
            <x-ui.insignia tono="info">{{ cantidad($mision->puntos, 'punto') }}</x-ui.insignia>
            <x-ui.insignia tono="neutro">{{ $mision->vence_at ? ($mision->vence_at->isFuture() ? __('jota.misiones.vence', ['hora' => cuando($mision->vence_at)]) : __('jota.misiones.estados.vencida')) : __('jota.misiones.sin_vencimiento') }}</x-ui.insignia>
        </div>
        <p class="whitespace-pre-line">{{ $mision->descripcion }}</p>
        @if ($patrulla && in_array($estado, ['nueva', 'rechazada'], true))
            <p class="text-sm text-texto-suave">{{ __('jota.misiones.patrulla_libre', ['nombre' => $patrulla]) }}</p>
        @endif
    </div>

    @switch($estado)
        @case('pendiente')
            <x-ui.aviso tono="aviso">{{ $entrega['entregada_por'] ? __('jota.misiones.patrulla_entrego', ['nombre' => $entrega['entregada_por']]) : __('jota.misiones.pendiente_aviso') }}</x-ui.aviso>
            @break
        @case('aprobada')
            <x-ui.aviso tono="exito">{{ $mision->esPorPatrulla()
                ? copia('jota.misiones.aprobada_aviso_patrulla', ['puntos' => cantidad($mision->puntos, 'punto')])
                : copia('jota.misiones.aprobada_aviso', ['puntos' => cantidad($mision->puntos, 'punto'), 'jlis' => cantidad($mision->puntos, 'jli')]) }}</x-ui.aviso>
            @break
        @case('rechazada')
            <x-ui.aviso tono="error" :titulo="__('jota.misiones.rechazada_aviso')">«{{ $entrega['motivo_rechazo'] }}»</x-ui.aviso>
            @break
        @case('vencida')
        @case('cerrada')
            <x-ui.aviso tono="error">{{ __('jota.misiones.vencida_aviso') }}</x-ui.aviso>
            @break
    @endswitch

    @if ($entrega && in_array($estado, ['pendiente', 'aprobada'], true) && ($entrega['texto'] || $entrega['imagenes']))
        <x-ui.tarjeta>
            <p class="text-sm font-bold">{{ __('jota.misiones.tu_entrega') }}</p>
            @if ($entrega['texto'])<p class="mt-1 whitespace-pre-line break-words">{{ $entrega['texto'] }}</p>@endif
            @if ($entrega['imagenes'])
                <div class="mt-3 grid grid-cols-2 gap-3">
                    @foreach ($entrega['imagenes'] as $i => $img)
                        <x-ui.foto :n="$i + 1" :src="$img['url']" :estado="$img['url'] ? 'lista' : 'procesando'" />
                    @endforeach
                </div>
            @endif
        </x-ui.tarjeta>
    @endif

    @if ($detalle['puede_entregar'])
        <x-ui.boton :href="route('app.entregar', $mision)" icono-final="arrow-right">{{ $estado === 'rechazada' ? __('jota.misiones.entregar_de_nuevo') : ($mision->esPorPatrulla() ? __('jota.misiones.entregar_patrulla') : __('jota.misiones.entregar')) }}</x-ui.boton>
    @elseif ($mision->esPorPatrulla() && ! auth()->user()->patrulla_id && $detalle['abierta'])
        <x-ui.aviso tono="aviso">{{ __('jota.misiones.error_sin_patrulla') }}</x-ui.aviso>
    @endif
</x-ui.pantalla>
