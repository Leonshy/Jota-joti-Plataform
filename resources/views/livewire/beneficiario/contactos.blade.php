<x-ui.pantalla wire:poll.30s :volver="route('app.inicio')" :titulo="__('jota.contactos.registro_'.$tipo)">
    <p class="text-texto-suave">{{ $esJota ? __($porUnidad ? 'jota.contactos.ayuda_jota_unidad' : 'jota.contactos.ayuda_jota') : __('jota.contactos.ayuda_joti') }}</p>

    <div class="flex flex-wrap gap-2">
        <x-ui.insignia tono="exito">{{ trans_choice('jota.contactos.aprobados', $aprobados, ['count' => $aprobados]) }}</x-ui.insignia>
        @if ($pendientes > 0)<x-ui.insignia tono="aviso">{{ trans_choice('jota.contactos.por_revisar', $pendientes, ['count' => $pendientes]) }}</x-ui.insignia>@endif
    </div>

    <x-ui.boton :href="route('app.contactos.nuevo', $tipo)" icono="plus">{{ __('jota.contactos.registrar') }}</x-ui.boton>

    @if ($filas === [])
        <x-ui.vacio :icono="$esJota ? 'signal' : 'globe-alt'" :titulo="__('jota.contactos.vacio_titulo')" :texto="__('jota.contactos.vacio_texto')" />
    @else
        <ul class="flex flex-col gap-3">
            @foreach ($filas as $f)
                <li wire:key="c-{{ $f['id'] }}">
                    <x-ui.tarjeta class="flex flex-col gap-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="break-words text-lg font-bold">{{ $f['titulo'] }}</p>
                                <p class="break-words text-sm text-texto-suave">{{ $f['detalle'] }}</p>
                            </div>
                            <x-ui.insignia :tono="['pendiente' => 'aviso', 'aprobada' => 'exito', 'rechazada' => 'error'][$f['estado']]">{{ __('jota.contactos.estado_'.$f['estado']) }}</x-ui.insignia>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.insignia tono="neutro" :icono="false">{{ $f['local'] ? __('jota.contactos.local') : __('jota.contactos.exterior') }}</x-ui.insignia>
                            @if ($f['estado'] === 'aprobada' && $f['puntos'])<x-ui.insignia tono="info" :icono="false">{{ cantidad($f['puntos'], 'punto') }}</x-ui.insignia>@endif
                            @if ($f['quien'])<span class="text-sm text-texto-suave">{{ __('jota.contactos.cargado_por', ['nombre' => $f['quien']]) }}</span>@endif
                        </div>
                        @if ($f['estado'] === 'rechazada')
                            <p class="text-sm font-bold text-error-texto">{{ __('jota.contactos.motivo', ['motivo' => $f['motivo']]) }}</p>
                            <p class="text-xs text-texto-suave">{{ __('jota.contactos.rechazado_ayuda') }}</p>
                        @endif
                    </x-ui.tarjeta>
                </li>
            @endforeach
        </ul>
    @endif
</x-ui.pantalla>
