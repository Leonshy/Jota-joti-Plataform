@php($estado ??= null)
<x-layouts.movil titulo="Actividades">
    <x-ui.pantalla :titulo="__('jota.actividades.titulo')">
        <x-slot:barra><x-ui.barra-participante activa="actividades" /></x-slot:barra>

        <x-ui.boton variante="secundario" icono="calendar-days" :href="url('/diseno/actividades?estado=reservas')">{{ __('jota.actividades.mis_reservas') }}</x-ui.boton>

        @if ($estado === 'choque')
            <x-ui.aviso tono="aviso">{{ __('jota.actividades.error_choque', ['actividad' => 'Gran juego de patrullas', 'quien' => 'la patrulla Águilas', 'inicio' => '15:00', 'fin' => '16:00']) }}</x-ui.aviso>
        @endif

        <x-ui.seccion titulo="Taller de nudos">
            <div class="flex flex-wrap gap-2">
                <x-ui.insignia tono="marca">{{ __('jota.actividades.taller') }}</x-ui.insignia>
                <x-ui.insignia tono="neutro">{{ __('jota.actividades.individual') }}</x-ui.insignia>
                <x-ui.insignia tono="info">{{ cantidad(20, 'punto') }}</x-ui.insignia>
            </div>

            <ul class="flex flex-col gap-3">
                <li>
                    <x-ui.tarjeta class="flex items-center justify-between gap-3">
                        <div>
                            <p class="numeros text-lg font-bold">15:00 – 16:00</p>
                            <p class="text-sm text-texto-suave">{{ trans_choice('jota.actividades.lugares', 7) }}</p>
                        </div>
                        <x-ui.boton :bloque="false" class="min-h-tactil! px-5!">{{ __('jota.actividades.reservar') }}</x-ui.boton>
                    </x-ui.tarjeta>
                </li>
                <li>
                    <x-ui.tarjeta class="flex items-center justify-between gap-3">
                        <div>
                            <p class="numeros text-lg font-bold">16:00 – 17:00</p>
                            <p class="text-sm text-texto-suave">{{ trans_choice('jota.actividades.lugares', 0) }}</p>
                        </div>
                        <x-ui.boton :bloque="false" :deshabilitado="true" class="min-h-tactil! px-5!">{{ __('jota.actividades.reservar') }}</x-ui.boton>
                    </x-ui.tarjeta>
                </li>
                <li>
                    <x-ui.tarjeta class="flex items-center justify-between gap-3">
                        <div>
                            <p class="numeros text-lg font-bold">17:00 – 18:00</p>
                            <p class="text-sm text-texto-suave">{{ trans_choice('jota.actividades.lugares', 2) }}</p>
                        </div>
                        <x-ui.boton :bloque="false" :href="url('/diseno/actividades?estado=choque')" class="min-h-tactil! px-5!">{{ __('jota.actividades.reservar') }}</x-ui.boton>
                    </x-ui.tarjeta>
                </li>
            </ul>
        </x-ui.seccion>

        <x-ui.seccion titulo="Gran juego de patrullas">
            <div class="flex flex-wrap gap-2">
                <x-ui.insignia tono="marca">{{ __('jota.actividades.estacion') }}</x-ui.insignia>
                <x-ui.insignia tono="neutro">{{ __('jota.actividades.equipo') }}</x-ui.insignia>
                <x-ui.insignia tono="info">{{ cantidad(60, 'punto') }}</x-ui.insignia>
            </div>
            <x-ui.aviso tono="info">{{ __('jota.actividades.reservas_equipo_aviso', ['nombre' => 'Águilas']) }}</x-ui.aviso>
        </x-ui.seccion>
    </x-ui.pantalla>
</x-layouts.movil>
