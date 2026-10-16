<x-ui.pantalla wire:poll.30s :volver="route('app.actividades')" :titulo="__('jota.actividades.mis_reservas')">
    @if ($mensaje)
        <x-ui.aviso tono="aviso">{{ $mensaje }}</x-ui.aviso>
    @endif

    @if ($reservas === [])
        <x-ui.vacio icono="calendar-days" :titulo="__('jota.actividades.sin_reservas')">
            <x-ui.boton :href="route('app.actividades')">{{ __('jota.actividades.ver_agenda') }}</x-ui.boton>
        </x-ui.vacio>
    @else
        <ul class="flex flex-col gap-3">
            @foreach ($reservas as $r)
                <li wire:key="r-{{ $r['id'] }}">
                    <x-ui.tarjeta class="flex flex-col gap-3">
                        <div>
                            <p class="font-bold">{{ $r['actividad'] }}</p>
                            <x-ui.lugar>{{ $r['lugar'] ?? '' }}</x-ui.lugar>
                            <p class="numeros text-lg font-bold text-titulo">{{ cuando($r['inicio']) }} – {{ $r['fin']->format('H:i') }}</p>
                            @if ($r['por_equipo'])<p class="text-sm text-texto-suave">{{ __('jota.actividades.para_equipo') }}</p>@endif
                        </div>

                        @if ($r['comenzado'])
                            <p class="text-sm text-texto-suave">{{ __('jota.actividades.ya_empezo') }}</p>
                        @elseif ($confirmar === $r['id'])
                            <div role="alertdialog" aria-labelledby="cancelar-{{ $r['id'] }}" class="flex flex-col gap-3 rounded-sm bg-error-fondo p-3">
                                <p id="cancelar-{{ $r['id'] }}" class="font-bold">{{ __('jota.actividades.cancelar_pregunta') }}</p>
                                <p class="text-sm">{{ __('jota.actividades.cancelar_texto') }}</p>
                                <x-ui.boton variante="peligro" wire:click="cancelar" wire:loading.attr="disabled" wire:target="cancelar">{{ __('jota.actividades.cancelar_si') }}</x-ui.boton>
                                <x-ui.boton variante="secundario" wire:click="noCancelar">{{ __('jota.actividades.cancelar_no') }}</x-ui.boton>
                            </div>
                        @else
                            <x-ui.boton variante="secundario" wire:click="pedirCancelar({{ $r['id'] }})">{{ __('jota.actividades.cancelar_reserva') }}</x-ui.boton>
                        @endif
                    </x-ui.tarjeta>
                </li>
            @endforeach
        </ul>
    @endif
</x-ui.pantalla>
