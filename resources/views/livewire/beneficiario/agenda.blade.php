<x-ui.pantalla wire:poll.30s :titulo="__('jota.navegacion.actividades')">
    <x-slot:barra><x-ui.barra-participante activa="actividades" modo="app" /></x-slot:barra>

    <x-ui.boton variante="secundario" icono="calendar-days" :href="route('app.reservas')">{{ __('jota.actividades.mis_reservas') }}</x-ui.boton>

    @if ($mensaje)
        <x-ui.aviso :tono="$tono">{{ $mensaje }}</x-ui.aviso>
    @endif

    {{-- Sin patrulla no se puede reservar por equipo (talleres por equipo y estaciones): se avisa una sola vez, arriba. --}}
    @if (! $patrulla && collect($actividades)->contains('por_equipo', true))
        <x-ui.aviso tono="aviso">{{ __('jota.actividades.error_sin_patrulla') }}</x-ui.aviso>
    @endif

    {{-- TALLERES: primero lo que está en curso, después con lugar, apuntado y lleno. --}}
    <x-ui.grupo :titulo="__('jota.actividades.grupo_talleres')" icono="academic-cap">
        @forelse ($talleres as $a)
            @include('livewire.beneficiario._actividad')
        @empty
            <p class="text-texto-suave">{{ __('jota.actividades.sin_talleres') }}</p>
        @endforelse
    </x-ui.grupo>

    {{-- ESTACIONES JOTA: reservada por mi equipo, disponibles y en curso de mi equipo. Las ocupadas o bloqueadas no se muestran. --}}
    <x-ui.grupo :titulo="__('jota.actividades.grupo_estaciones')" icono="map-pin">
        @if ($patrulla)
            @if ($estacion)
                <x-ui.aviso tono="info">{{ __('jota.actividades.estacion_unica_aviso', ['actividad' => $estacion['actividad'], 'cuando' => cuando($estacion['inicio']).' – '.$estacion['fin']->format('H:i')]) }}</x-ui.aviso>
            @else
                <x-ui.aviso tono="info">{{ __('jota.actividades.reservas_equipo_aviso', ['nombre' => $patrulla]) }} {{ __('jota.actividades.una_estacion_a_la_vez') }}</x-ui.aviso>
            @endif
        @endif

        @forelse ($estaciones as $a)
            @include('livewire.beneficiario._actividad')
        @empty
            <p class="text-texto-suave">{{ __('jota.actividades.sin_estaciones') }}</p>
        @endforelse
    </x-ui.grupo>
</x-ui.pantalla>
