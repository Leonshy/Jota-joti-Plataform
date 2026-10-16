<x-ui.pantalla :volver="route('dirigente.inicio')" :titulo="__('jota.dirigente.revisar_titulo')" :h1-propio="true">
    @if ($paso === 'aprobada')
        <x-ui.resultado tono="exito" :titulo="__('jota.dirigente.aprobada_titulo')"
            :texto="$porPatrulla
                ? copia('jota.dirigente.acredita_patrulla', ['puntos' => cantidad($puntos, 'punto'), 'jlis' => cantidad($cuota, 'jli')])
                : copia('jota.dirigente.acredita_individual', ['puntos' => cantidad($puntos, 'punto'), 'jlis' => cantidad($puntos, 'jli'), 'nombre' => $nombre])">
            @if ($siguiente)<x-ui.boton :href="route('dirigente.revisar', $siguiente)">{{ __('jota.dirigente.siguiente') }}</x-ui.boton>@endif
            <x-ui.boton :variante="$siguiente ? 'secundario' : 'primario'" :href="route('dirigente.inicio')">{{ __('jota.dirigente.volver_cola') }}</x-ui.boton>
        </x-ui.resultado>
    @elseif ($paso === 'rechazada')
        <x-ui.resultado tono="exito" :titulo="__('jota.dirigente.rechazada_titulo')" :texto="__('jota.dirigente.rechazada_texto', ['nombre' => $nombre])">
            @if ($siguiente)<x-ui.boton :href="route('dirigente.revisar', $siguiente)">{{ __('jota.dirigente.siguiente') }}</x-ui.boton>@endif
            <x-ui.boton :variante="$siguiente ? 'secundario' : 'primario'" :href="route('dirigente.inicio')">{{ __('jota.dirigente.volver_cola') }}</x-ui.boton>
        </x-ui.resultado>
    @elseif ($paso === 'ya')
        <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.dirigente.ya_revisada_titulo')" :texto="__('jota.dirigente.ya_revisada_texto')">
            @if ($siguiente)<x-ui.boton :href="route('dirigente.revisar', $siguiente)">{{ __('jota.dirigente.siguiente') }}</x-ui.boton>@endif
            <x-ui.boton :variante="$siguiente ? 'secundario' : 'primario'" :href="route('dirigente.inicio')">{{ __('jota.dirigente.volver_cola') }}</x-ui.boton>
        </x-ui.resultado>
    @elseif ($paso === 'rechazar')
        <h1 class="text-xl text-titulo">{{ __('jota.dirigente.rechazar_a', ['nombre' => $porPatrulla ? $titulo : $nombre]) }}</h1>
        <form wire:submit="rechazar" class="flex flex-col gap-4" novalidate>
            <x-ui.campo name="motivo" wire:model="motivo" :label="__('jota.dirigente.motivo_label')" :area="true" autofocus
                :ayuda="$errors->has('motivo') ? null : __('jota.dirigente.motivo_ayuda', ['nombre' => $nombre])" :error="$errors->first('motivo') ?: null" />
            <x-ui.boton variante="peligro" type="submit" wire:loading.attr="disabled" wire:target="rechazar">{{ __('jota.dirigente.rechazar') }}</x-ui.boton>
            <x-ui.boton variante="secundario" type="button" wire:click="cancelarRechazo">{{ __('jota.comun.cancelar') }}</x-ui.boton>
        </form>
    @else
        <div>
            <h1 class="text-2xl text-titulo">{{ $titulo }}</h1>
            @if ($subtitulo)<p class="text-texto-suave">{{ $subtitulo }}</p>@endif
        </div>

        <x-ui.tarjeta>
            <p class="text-sm text-texto-suave">{{ __('jota.dirigente.mision') }}</p>
            <p class="font-bold">{{ $mision }}</p>
            <p class="mt-1 text-sm">{{ $porPatrulla
                ? copia('jota.dirigente.acredita_patrulla', ['puntos' => cantidad($puntos, 'punto'), 'jlis' => cantidad($cuota, 'jli')])
                : copia('jota.dirigente.acredita_individual', ['puntos' => cantidad($puntos, 'punto'), 'jlis' => cantidad($puntos, 'jli'), 'nombre' => $nombre]) }}</p>
        </x-ui.tarjeta>

        @if ($texto)
            <x-ui.tarjeta>
                <p class="text-sm text-texto-suave">{{ __('jota.dirigente.texto') }}</p>
                <p class="mt-1 whitespace-pre-line break-words">{{ $texto }}</p>
            </x-ui.tarjeta>
        @endif

        @if ($imagenes)
            <x-ui.seccion :titulo="__('jota.dirigente.fotos')">
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($imagenes as $i => $url)
                        <x-ui.foto :n="$i + 1" :src="$url" :estado="$url ? 'lista' : 'procesando'" />
                    @endforeach
                </div>
            </x-ui.seccion>
        @endif

        <div class="sticky bottom-3 grid grid-cols-2 gap-3 rounded-lg bg-fondo/95 p-2 shadow-elevada">
            <x-ui.boton variante="secundario" type="button" wire:click="pedirRechazo">{{ __('jota.dirigente.rechazar') }}</x-ui.boton>
            <x-ui.boton type="button" icono="check" wire:click="aprobar" wire:loading.attr="disabled" wire:target="aprobar">{{ __('jota.dirigente.aprobar') }}</x-ui.boton>
        </div>
    @endif
</x-ui.pantalla>
