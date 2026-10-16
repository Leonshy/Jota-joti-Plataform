<x-ui.pantalla wire:poll.10s :titulo="__('jota.dirigente.asistencia_titulo')">
    <x-slot:barra><x-ui.barra-dirigente activa="asistencia" modo="app" /></x-slot:barra>

    <x-ui.selector-unidad />

    @if ($error)<x-ui.aviso tono="aviso">{{ $error }}</x-ui.aviso>@endif

    @if ($filas === [])
        <x-ui.vacio icono="qr-code" :titulo="__('jota.actividades.vacio')" />
    @else
        <x-ui.lista>
            @foreach ($filas as $f)
                @if ($f['por_equipo'])
                    <x-ui.fila :href="route('dirigente.cumplimientos.actividad', $f['id'])" icono="user-group" :titulo="$f['titulo']" :subtitulo="__('jota.actividades.equipo').' · '.cantidad($f['puntos'], 'punto')">
                        <x-slot:derecha><x-ui.insignia tono="marca" :icono="false">{{ __('jota.navegacion.cumplimientos') }}</x-ui.insignia></x-slot:derecha>
                    </x-ui.fila>
                @elseif ($f['sesion'])
                    <x-ui.fila :href="route('dirigente.sesion', $f['sesion'])" icono="qr-code" :titulo="$f['titulo']" :subtitulo="__('jota.actividades.individual').' · '.cantidad($f['puntos'], 'punto')">
                        <x-slot:derecha><x-ui.insignia tono="exito">{{ __('jota.dirigente.asistencia_abierta', ['n' => $f['asistentes']]) }}</x-ui.insignia></x-slot:derecha>
                    </x-ui.fila>
                @else
                    <li>
                        <button type="button" wire:click="abrir({{ $f['id'] }})" wire:loading.attr="disabled" wire:target="abrir({{ $f['id'] }})"
                            class="flex min-h-16 w-full items-center gap-3 px-4 py-3 text-left transition duration-150 hover:bg-fondo-suave active:bg-primario-suave">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primario-suave text-primario"><x-heroicon-o-qr-code class="size-6" aria-hidden="true" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block break-words font-bold leading-snug">{{ $f['titulo'] }}</span>
                                <span class="block text-sm text-texto-suave">{{ __('jota.actividades.individual') }} · {{ cantidad($f['puntos'], 'punto') }}</span>
                            </span>
                            <x-ui.insignia tono="marca" :icono="false" class="shrink-0">{{ __('jota.dirigente.abrir_sesion') }}</x-ui.insignia>
                            <x-heroicon-o-chevron-right class="size-5 shrink-0 text-texto-suave" aria-hidden="true" />
                        </button>
                    </li>
                @endif
            @endforeach
        </x-ui.lista>

        <x-ui.aviso tono="info">{{ __('jota.dirigente.abrir_ayuda') }}</x-ui.aviso>
    @endif
</x-ui.pantalla>
