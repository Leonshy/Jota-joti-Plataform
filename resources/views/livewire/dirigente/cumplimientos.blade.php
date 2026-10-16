<x-ui.pantalla :volver="$actividad ? route('dirigente.cumplimientos') : null" :titulo="__('jota.dirigente.cumplimientos_titulo')" :h1-propio="(bool) $actividad">
    <x-slot:barra><x-ui.barra-dirigente activa="cumplimientos" modo="app" /></x-slot:barra>

    <x-ui.selector-unidad />

    @if (! $actividad)
        @if ($lista === [])
            <x-ui.vacio icono="check-badge" :titulo="__('jota.dirigente.cumplimientos_vacio')" />
        @else
            <x-ui.lista>
                @foreach ($lista as $a)
                    <x-ui.fila :href="route('dirigente.cumplimientos.actividad', $a['id'])" icono="user-group" :titulo="$a['titulo']" :subtitulo="cantidad($a['puntos'], 'punto').' '.__('jota.dirigente.a_la_patrulla')" />
                @endforeach
            </x-ui.lista>
        @endif
    @else
        <div>
            <h1 class="text-xl text-titulo">{{ $actividad['titulo'] }}</h1>
            <p class="text-texto-suave">{{ cantidad($actividad['puntos'], 'punto') }} {{ __('jota.dirigente.a_la_patrulla') }}</p>
        </div>

        @if ($mensaje)<x-ui.aviso tono="aviso">{{ $mensaje }}</x-ui.aviso>@endif

        @if ($patrullas === [])
            <x-ui.vacio icono="user-group" :titulo="__('jota.dirigente.sin_patrullas')" />
        @else
            <ul class="flex flex-col gap-3">
                @foreach ($patrullas as $p)
                    <li wire:key="p-{{ $p['id'] }}">
                        <x-ui.tarjeta>
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-bold">{{ $p['nombre'] }}</p>
                                <x-ui.insignia :tono="['sin_marcar' => 'neutro', 'cumplida' => 'exito', 'no_cumplida' => 'error'][$p['estado']]" :icono="$p['estado'] !== 'sin_marcar'">{{ __('jota.dirigente.'.$p['estado']) }}</x-ui.insignia>
                            </div>

                            @if ($p['estado'] === 'cumplida')
                                <p class="mt-1 text-sm text-texto-suave">{{ __('jota.dirigente.cumplida_definitiva') }}</p>
                            @elseif ($confirmar === $p['id'])
                                <div role="alertdialog" aria-labelledby="cumplida-{{ $p['id'] }}" class="mt-3 flex flex-col gap-3 rounded-sm bg-aviso-fondo p-3">
                                    <p id="cumplida-{{ $p['id'] }}" class="font-bold">{{ __('jota.dirigente.cumplida_pregunta', ['nombre' => $p['nombre']]) }}</p>
                                    <p class="text-sm">{{ $p['cada_uno']
                                        ? copia('jota.dirigente.cumplida_aviso_cada', ['puntos' => cantidad($actividad['puntos'], 'punto'), 'jlis' => cantidad($actividad['puntos'], 'jli')])
                                        : copia('jota.dirigente.cumplida_aviso', ['puntos' => cantidad($actividad['puntos'], 'punto'), 'jlis' => cantidad($p['cuota'], 'jli')]) }}</p>
                                    <x-ui.boton wire:click="cumplida" wire:loading.attr="disabled" wire:target="cumplida">{{ __('jota.dirigente.cumplida_si') }}</x-ui.boton>
                                    <x-ui.boton variante="secundario" wire:click="noConfirmar">{{ __('jota.comun.cancelar') }}</x-ui.boton>
                                </div>
                            @elseif ($p['estado'] === 'no_cumplida')
                                <x-ui.boton variante="secundario" class="mt-3 min-h-tactil! text-sm!" wire:click="pedirCumplida('{{ $p['id'] }}')">{{ __('jota.dirigente.corregir_cumplida') }}</x-ui.boton>
                            @else
                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    <x-ui.boton variante="secundario" class="min-h-tactil! text-sm!" wire:click="noCumplida('{{ $p['id'] }}')" wire:loading.attr="disabled">{{ __('jota.dirigente.no_cumplida') }}</x-ui.boton>
                                    <x-ui.boton class="min-h-tactil! text-sm!" wire:click="pedirCumplida('{{ $p['id'] }}')">{{ __('jota.dirigente.cumplida') }}</x-ui.boton>
                                </div>
                            @endif
                        </x-ui.tarjeta>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</x-ui.pantalla>
