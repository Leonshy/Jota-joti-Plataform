{{-- Una actividad (taller o estación) con sus turnos. Recibe $a, $patrulla y $estacion de la agenda. --}}
    <x-ui.seccion :titulo="$a['titulo']" :nivel="3" wire:key="a-{{ $a['id'] }}">
        <x-ui.lugar>{{ $a['lugar'] ?? '' }}</x-ui.lugar>
        {{-- En los talleres la gente se APUNTA; en las estaciones JOTA el equipo RESERVA. --}}
        @php($etiqueta = $a['taller'] ? __($a['por_equipo'] ? 'jota.actividades.apuntar_equipo' : 'jota.actividades.apuntarme') : __('jota.actividades.reservar'))
        @php($yaApuntado = $a['taller'] && ($a['apuntado'] ?? false))
        <div class="flex flex-wrap gap-2">
            <x-ui.insignia tono="marca">{{ $a['taller'] ? __('jota.actividades.taller') : __('jota.actividades.estacion') }}</x-ui.insignia>
            <x-ui.insignia tono="neutro">{{ $a['por_equipo'] ? __('jota.actividades.equipo') : __('jota.actividades.individual') }}</x-ui.insignia>
            @if ($a['puntos'] > 0)<x-ui.insignia tono="info">{{ cantidad($a['puntos'], 'punto') }}</x-ui.insignia>@endif
            @if ($yaApuntado)<x-ui.insignia tono="exito">{{ __('jota.actividades.apuntado') }}</x-ui.insignia>@endif
        </div>
        @if ($yaApuntado)
            <p class="text-sm text-texto-suave">{{ __($a['por_equipo'] ? 'jota.actividades.taller_una_vez_equipo' : 'jota.actividades.taller_una_vez') }}</p>
        @endif

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
                                    · @if ($t['comenzado'] && ! $a['taller']) {{ __('jota.actividades.en_curso') }}
                                    @elseif (! $a['taller']) {{-- Estación JOTA: un solo equipo por horario, solo está disponible u ocupado --}}
                                        {{ $t['reservado'] ? __('jota.actividades.reservado') : ($t['lugares'] === 0 ? __('jota.actividades.ocupado') : __('jota.actividades.disponible')) }}
                                    @else
                                        {{-- Taller: se puede anotar hasta que el turno termina, aunque ya esté en curso. --}}
                                        @if ($t['comenzado']){{ __('jota.actividades.en_curso_sumarse') }} · @endif
                                        {{ $t['lugares'] === null ? __('jota.actividades.sin_limite') : trans_choice($a['por_equipo'] ? 'jota.actividades.lugares_patrullas' : 'jota.actividades.lugares', $t['lugares']) }}
                                    @endif
                                </p>
                            </div>
                            @if ($t['reservado'])
                                <x-ui.insignia tono="exito">{{ $a['taller'] ? __('jota.actividades.apuntado') : __('jota.actividades.reservado') }}</x-ui.insignia>
                            @elseif ($t['cerrado'] || $t['lugares'] === 0 || ($a['por_equipo'] && ! $patrulla) || (! $a['taller'] && $estacion) || $yaApuntado)
                                <x-ui.boton :bloque="false" :deshabilitado="true" class="min-h-tactil! px-5!">{{ $etiqueta }}</x-ui.boton>
                            @else
                                <x-ui.boton :bloque="false" aria-label="{{ $etiqueta.' '.$a['titulo'].', '.$t['inicio']->format('H:i') }}" wire:click="reservar({{ $t['id'] }})" wire:loading.attr="disabled" wire:target="reservar({{ $t['id'] }})" class="min-h-tactil! px-5!">{{ $etiqueta }}</x-ui.boton>
                            @endif
                        </x-ui.tarjeta>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.seccion>
