<x-ui.pantalla wire:poll.30s :titulo="__('jota.tienda.historial_titulo')">
    <x-slot:barra><x-ui.barra-tienda activa="historial" modo="app" /></x-slot:barra>

    <x-ui.tarjeta tono="info" class="text-center">
        <p class="text-sm">{{ __('jota.tienda.total_cobrado') }}</p>
        <p class="numeros text-4xl font-bold text-violeta-900">{{ number_format($total, 0, ',', '.') }} <span class="text-2xl">{{ trans_choice('jota.unidad.jli', $total) }}</span></p>
    </x-ui.tarjeta>

    <x-ui.seccion :titulo="__('jota.tienda.cobros')">
        @if ($cobros === [])
            <p class="text-texto-suave">{{ __('jota.tienda.historial_vacio') }}</p>
        @else
            <x-ui.lista>
                @foreach ($cobros as $c)
                    <x-ui.fila :icono="$c['estado'] === 'pagado' ? 'jota-moneda' : 'clock'" :titulo="$c['quien'] ?? '—'" :subtitulo="$c['cuando']">
                        <x-slot:derecha>
                            @if ($c['estado'] === 'pagado')
                                <span class="numeros font-bold">{{ number_format($c['monto'], 0, ',', '.') }}</span>
                            @else
                                <x-ui.insignia tono="neutro" :icono="false">{{ $c['estado'] === 'vencido' ? __('jota.tienda.estado_vencido') : __('jota.tienda.estado_pendiente') }}</x-ui.insignia>
                            @endif
                        </x-slot:derecha>
                    </x-ui.fila>
                @endforeach
            </x-ui.lista>
        @endif
    </x-ui.seccion>

    @if ($devoluciones)
        <x-ui.seccion :titulo="__('jota.tienda.devoluciones')">
            <x-ui.lista>
                @foreach ($devoluciones as $d)
                    <x-ui.fila icono="arrow-uturn-left" :titulo="$d['quien']" :subtitulo="'«'.$d['motivo'].'» · '.$d['cuando']">
                        <x-slot:derecha><span class="numeros font-bold text-exito-texto">+{{ number_format($d['monto'], 0, ',', '.') }}</span></x-slot:derecha>
                    </x-ui.fila>
                @endforeach
            </x-ui.lista>
        </x-ui.seccion>
    @endif
</x-ui.pantalla>
