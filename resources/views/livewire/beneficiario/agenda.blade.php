<x-ui.pantalla wire:poll.30s :titulo="__('jota.navegacion.actividades')">
    <x-slot:barra><x-ui.barra-participante activa="actividades" modo="app" /></x-slot:barra>

    <x-ui.boton variante="secundario" icono="calendar-days" :href="route('app.reservas')">{{ __('jota.actividades.mis_reservas') }}</x-ui.boton>

    @if ($mensaje)
        <x-ui.aviso :tono="$tono">{{ $mensaje }}</x-ui.aviso>
    @endif

    @if ($actividades === [])
        <x-ui.vacio icono="calendar-days" :titulo="__('jota.actividades.vacio')" />
    @endif

    @php($hayEquipo = collect($actividades)->contains('por_equipo', true))
    @if ($hayEquipo)
        {{-- Una sola vez para toda la pantalla: de quién es la reserva y la regla de las estaciones. --}}
        @if (! $patrulla)
            <x-ui.aviso tono="aviso">{{ __('jota.actividades.error_sin_patrulla') }}</x-ui.aviso>
        @elseif ($estacion)
            <x-ui.aviso tono="info">{{ __('jota.actividades.estacion_unica_aviso', ['actividad' => $estacion['actividad'], 'cuando' => cuando($estacion['inicio']).' – '.$estacion['fin']->format('H:i')]) }}</x-ui.aviso>
        @else
            <x-ui.aviso tono="info">{{ __('jota.actividades.reservas_equipo_aviso', ['nombre' => $patrulla]) }} {{ __('jota.actividades.una_estacion_a_la_vez') }}</x-ui.aviso>
        @endif
    @endif

    @foreach ($actividades as $a)
        <x-ui.seccion :titulo="$a['titulo']" wire:key="a-{{ $a['id'] }}">
            <div class="flex flex-wrap gap-2">
                <x-ui.insignia tono="marca">{{ $a['taller'] ? __('jota.actividades.taller') : __('jota.actividades.estacion') }}</x-ui.insignia>
                <x-ui.insignia tono="neutro">{{ $a['por_equipo'] ? __('jota.actividades.equipo') : __('jota.actividades.individual') }}</x-ui.insignia>
                @if ($a['puntos'] > 0)<x-ui.insignia tono="info">{{ cantidad($a['puntos'], 'punto') }}</x-ui.insignia>@endif
            </div>

            @if ($a['turnos'] === [])
                <p class="text-texto-suave">{{ __('jota.actividades.sin_turnos') }}</p>
            @else
                <ul class="flex flex-col gap-3">
                    @foreach ($a['turnos'] as $t)
                        <li wire:key="t-{{ $t['id'] }}">
                            <x-ui.tarjeta class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="numeros text-lg font-bold">{{ $t['inicio']->format('H:i') }} – {{ $t['fin']->format('H:i') }}</p>
                                    <p class="text-sm text-texto-suave">
                                        {{ $t['inicio']->isToday() ? __('jota.comun.hoy') : $t['inicio']->locale('es')->isoFormat('ddd D MMM') }}
                                        · @if ($t['comenzado']) {{ __('jota.actividades.en_curso') }}
                                        @elseif (! $a['taller']) {{-- Estación JOTA: un solo equipo por horario, solo está disponible u ocupado --}}
                                            {{ $t['reservado'] ? __('jota.actividades.reservado') : ($t['lugares'] === 0 ? __('jota.actividades.ocupado') : __('jota.actividades.disponible')) }}
                                        @else
                                            {{ $t['lugares'] === null ? __('jota.actividades.sin_limite') : trans_choice($a['por_equipo'] ? 'jota.actividades.lugares_patrullas' : 'jota.actividades.lugares', $t['lugares']) }}
                                        @endif
                                    </p>
                                </div>
                                @if ($t['reservado'])
                                    <x-ui.insignia tono="exito">{{ __('jota.actividades.reservado') }}</x-ui.insignia>
                                @elseif ($t['comenzado'] || $t['lugares'] === 0 || ($a['por_equipo'] && ! $patrulla) || (! $a['taller'] && $estacion))
                                    <x-ui.boton :bloque="false" :deshabilitado="true" class="min-h-tactil! px-5!">{{ __('jota.actividades.reservar') }}</x-ui.boton>
                                @else
                                    <x-ui.boton :bloque="false" aria-label="{{ __('jota.actividades.reservar').' '.$a['titulo'].', '.$t['inicio']->format('H:i') }}" wire:click="reservar({{ $t['id'] }})" wire:loading.attr="disabled" wire:target="reservar({{ $t['id'] }})" class="min-h-tactil! px-5!">{{ __('jota.actividades.reservar') }}</x-ui.boton>
                                @endif
                            </x-ui.tarjeta>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.seccion>
    @endforeach
</x-ui.pantalla>
