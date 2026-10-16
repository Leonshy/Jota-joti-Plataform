<x-ui.pantalla wire:poll.30s :volver="route('dirigente.inicio')" :titulo="__('jota.dirigente.reservas_titulo')">
    <x-slot:barra><x-ui.barra-dirigente activa="cola" modo="app" /></x-slot:barra>

    <x-ui.selector-unidad />

    <x-ui.campo name="buscar" wire:model.live.debounce.300ms="buscar" :label="__('jota.dirigente.reservas_buscar')" type="search" />

    @if ($grupos === [])
        <x-ui.vacio icono="calendar-days" :titulo="$buscar === '' ? __('jota.dirigente.reservas_vacio') : __('jota.dirigente.reservas_sin_resultados')" />
    @endif

    @foreach ($grupos as $filas)
        @php($t = $filas[0])
        <x-ui.seccion :titulo="$t['actividad']" wire:key="g-{{ $t['turno'] }}">
            <p class="numeros -mt-2 text-sm text-texto-suave">{{ cuando($t['inicio']) }} – {{ $t['fin']->format('H:i') }}@if ($t['comenzado']) · {{ __('jota.actividades.en_curso') }}@endif</p>
            <ul class="flex flex-col gap-3">
                @foreach ($filas as $r)
                    <li wire:key="r-{{ $r['id'] }}">
                        <x-ui.tarjeta class="flex flex-col gap-3">
                            <div>
                                <p class="font-bold">{{ $r['quien'] }}</p>
                                @if ($r['detalle'])<p class="text-sm text-texto-suave">{{ $r['detalle'] }}</p>@endif
                            </div>

                            @if ($confirmar === $r['id'])
                                <div role="alertdialog" aria-labelledby="cancelar-{{ $r['id'] }}" class="flex flex-col gap-3 rounded-sm bg-error-fondo p-3">
                                    <p id="cancelar-{{ $r['id'] }}" class="font-bold">{{ __('jota.dirigente.reservas_cancelar_pregunta', ['quien' => $r['quien']]) }}</p>
                                    <p class="text-sm">{{ __('jota.actividades.cancelar_texto') }}</p>
                                    <x-ui.boton variante="peligro" wire:click="cancelar" wire:loading.attr="disabled" wire:target="cancelar">{{ __('jota.actividades.cancelar_si') }}</x-ui.boton>
                                    <x-ui.boton variante="secundario" wire:click="noCancelar">{{ __('jota.actividades.cancelar_no') }}</x-ui.boton>
                                </div>
                            @else
                                <x-ui.boton variante="secundario" aria-label="{{ __('jota.actividades.cancelar_reserva').': '.$r['quien'] }}" wire:click="pedirCancelar({{ $r['id'] }})">{{ __('jota.actividades.cancelar_reserva') }}</x-ui.boton>
                            @endif
                        </x-ui.tarjeta>
                    </li>
                @endforeach
            </ul>
        </x-ui.seccion>
    @endforeach
</x-ui.pantalla>
