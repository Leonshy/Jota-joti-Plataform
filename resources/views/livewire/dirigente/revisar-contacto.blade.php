<x-ui.pantalla :volver="route('dirigente.contactos')" :titulo="__('jota.contactos.dir_revisar_titulo')" :h1-propio="true">
    @if ($paso === 'aprobada')
        <x-ui.resultado tono="exito" :titulo="__('jota.contactos.dir_aprobado_titulo')"
            :texto="$esJota
                ? ($porUnidad ? copia('jota.contactos.dir_acredita_rovers', ['puntos' => cantidad($puntos ?? 0, 'punto'), 'jlis' => cantidad($puntos ?? 0, 'jli')])
                    : copia('jota.contactos.dir_acredita_jota', ['puntos' => cantidad($puntos ?? 0, 'punto'), 'jlis' => cantidad($cuota, 'jli')]))
                : copia('jota.contactos.dir_acredita_joti', ['puntos' => cantidad($puntos ?? 0, 'punto'), 'jlis' => cantidad($puntos ?? 0, 'jli'), 'nombre' => $nombre])">
            @if ($siguiente)<x-ui.boton :href="route('dirigente.contacto', $siguiente)">{{ __('jota.contactos.dir_siguiente') }}</x-ui.boton>@endif
            <x-ui.boton :variante="$siguiente ? 'secundario' : 'primario'" :href="route('dirigente.contactos')">{{ __('jota.contactos.dir_volver') }}</x-ui.boton>
        </x-ui.resultado>
    @elseif ($paso === 'rechazada')
        <x-ui.resultado tono="exito" :titulo="__('jota.contactos.dir_rechazado_titulo')" :texto="__('jota.contactos.dir_rechazado_texto', ['nombre' => $nombre])">
            @if ($siguiente)<x-ui.boton :href="route('dirigente.contacto', $siguiente)">{{ __('jota.contactos.dir_siguiente') }}</x-ui.boton>@endif
            <x-ui.boton :variante="$siguiente ? 'secundario' : 'primario'" :href="route('dirigente.contactos')">{{ __('jota.contactos.dir_volver') }}</x-ui.boton>
        </x-ui.resultado>
    @elseif ($paso === 'ya')
        <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.contactos.dir_ya_titulo')" :texto="__('jota.contactos.dir_ya_texto')">
            @if ($siguiente)<x-ui.boton :href="route('dirigente.contacto', $siguiente)">{{ __('jota.contactos.dir_siguiente') }}</x-ui.boton>@endif
            <x-ui.boton :variante="$siguiente ? 'secundario' : 'primario'" :href="route('dirigente.contactos')">{{ __('jota.contactos.dir_volver') }}</x-ui.boton>
        </x-ui.resultado>
    @elseif ($paso === 'rechazar')
        <h1 class="text-xl text-titulo">{{ __('jota.contactos.dir_rechazar_a', ['nombre' => $nombre]) }}</h1>
        <form wire:submit="rechazar" class="flex flex-col gap-4" novalidate>
            <x-ui.campo name="motivo" wire:model="motivo" :label="__('jota.contactos.dir_motivo')" :area="true" autofocus
                :ayuda="$errors->has('motivo') ? null : __('jota.contactos.dir_motivo_ayuda', ['nombre' => $nombre])" :error="$errors->first('motivo') ?: null" />
            <x-ui.boton variante="peligro" type="submit" wire:loading.attr="disabled" wire:target="rechazar">{{ __('jota.contactos.dir_rechazar') }}</x-ui.boton>
            <x-ui.boton variante="secundario" type="button" wire:click="cancelarRechazo">{{ __('jota.comun.cancelar') }}</x-ui.boton>
        </form>
    @else
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl text-titulo">{{ $esJota ? $datos['estacion'] : $datos['ciudad'].', '.$datos['pais'] }}</h1>
            <p class="text-texto-suave">{{ __('jota.contactos.cargado_por', ['nombre' => $quien]) }}@if ($equipo) · {{ $equipo }}@endif</p>
            <div class="mt-1 flex flex-wrap gap-2">
                <x-ui.insignia tono="marca" :icono="false">{{ __('jota.contactos.'.$tipo) }}</x-ui.insignia>
                <x-ui.insignia tono="neutro" :icono="false">{{ $local ? __('jota.contactos.local') : __('jota.contactos.exterior') }}</x-ui.insignia>
            </div>
        </div>

        <x-ui.tarjeta>
            <p class="text-sm text-texto-suave">{{ __('jota.contactos.dir_datos') }}</p>
            <dl class="mt-2 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                @if ($esJota)
                    <dt class="text-texto-suave">{{ __('jota.contactos.fecha') }}</dt><dd class="numeros font-bold">{{ $datos['fecha_hora'] }}</dd>
                    <dt class="text-texto-suave">{{ __('jota.contactos.estacion') }}</dt><dd class="font-bold">{{ $datos['estacion'] }}</dd>
                    <dt class="text-texto-suave">RST</dt><dd class="numeros font-bold">{{ $datos['rst'] }}</dd>
                    <dt class="text-texto-suave">{{ __('jota.contactos.frecuencia') }}</dt><dd class="numeros font-bold">{{ $datos['frecuencia'] }}</dd>
                    <dt class="text-texto-suave">{{ __('jota.contactos.emision') }}</dt><dd class="font-bold">{{ $datos['emision'] }}</dd>
                    <dt class="text-texto-suave">QSL</dt><dd class="font-bold">{{ collect([$datos['qsl_r'] ? __('jota.contactos.qsl_r') : null, $datos['qsl_e'] ? __('jota.contactos.qsl_e') : null])->filter()->implode(' · ') ?: '—' }}</dd>
                    @if ($datos['obs'])<dt class="text-texto-suave">{{ __('jota.contactos.obs') }}</dt><dd class="break-words font-bold">{{ $datos['obs'] }}</dd>@endif
                @else
                    <dt class="text-texto-suave">{{ __('jota.contactos.grupo_scout') }}</dt><dd class="break-words font-bold">{{ $datos['grupo_scout'] }}</dd>
                    <dt class="text-texto-suave">{{ __('jota.contactos.unidad_scout') }}</dt><dd class="break-words font-bold">{{ $datos['unidad_scout'] }}</dd>
                    <dt class="text-texto-suave">{{ __('jota.contactos.ciudad') }}</dt><dd class="break-words font-bold">{{ $datos['ciudad'] }}</dd>
                    <dt class="text-texto-suave">{{ __('jota.contactos.pais') }}</dt><dd class="break-words font-bold">{{ $datos['pais'] }}</dd>
                @endif
            </dl>
        </x-ui.tarjeta>

        @unless ($esJota)
            <x-ui.seccion :titulo="__('jota.contactos.dir_captura')">
                @if ($captura)
                    <x-ui.foto :n="1" :src="$captura" />
                @else
                    <x-ui.foto :n="1" estado="procesando" />
                @endif
            </x-ui.seccion>
        @endunless

        @if ($puntos === null || $sinPuntaje)
            <x-ui.aviso tono="aviso">{{ __('jota.contactos.dir_sin_puntaje') }}</x-ui.aviso>
        @else
            <x-ui.tarjeta tono="suave">
                <p class="text-sm">{{ $esJota
                    ? ($porUnidad ? copia('jota.contactos.dir_acredita_rovers', ['puntos' => cantidad($puntos, 'punto'), 'jlis' => cantidad($puntos, 'jli')])
                        : copia('jota.contactos.dir_acredita_jota', ['puntos' => cantidad($puntos, 'punto'), 'jlis' => cantidad($cuota, 'jli')]))
                    : copia('jota.contactos.dir_acredita_joti', ['puntos' => cantidad($puntos, 'punto'), 'jlis' => cantidad($puntos, 'jli'), 'nombre' => $nombre]) }}</p>
            </x-ui.tarjeta>
        @endif

        <div class="sticky bottom-3 grid grid-cols-2 gap-3 rounded-lg bg-fondo/95 p-2 shadow-elevada">
            <x-ui.boton variante="secundario" type="button" wire:click="pedirRechazo">{{ __('jota.contactos.dir_rechazar') }}</x-ui.boton>
            <x-ui.boton type="button" icono="check" wire:click="aprobar" wire:loading.attr="disabled" wire:target="aprobar" :deshabilitado="$puntos === null">{{ __('jota.contactos.dir_aprobar') }}</x-ui.boton>
        </div>
    @endif
</x-ui.pantalla>
