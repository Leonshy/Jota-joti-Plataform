<x-ui.pantalla :volver="route('app.inicio')" :titulo="__('jota.historial.titulo')">
    <x-slot:barra><x-ui.barra-participante activa="inicio" modo="app" /></x-slot:barra>

    <nav aria-label="{{ __('jota.historial.filtrar') }}" class="flex gap-2">
        @foreach (['todo' => 'todo', 'puntos' => 'puntos', 'jlis' => 'jlis'] as $valor => $clave)
            <button type="button" wire:click="elegir('{{ $valor }}')" @if ($filtroActual === $valor) aria-current="page" @endif
                @class([
                    'grid min-h-tactil place-items-center rounded-full px-5 text-sm font-bold transition duration-150',
                    'bg-primario text-sobre-primario' => $filtroActual === $valor,
                    'bg-primario-suave text-primario' => $filtroActual !== $valor,
                ])>{{ __('jota.historial.'.$clave) }}</button>
        @endforeach
    </nav>

    @if ($h['filas'] === [])
        <x-ui.vacio icono="clock" :titulo="__('jota.historial.vacio_titulo')" :texto="__('jota.historial.vacio_texto')">
            <x-ui.boton :href="route('app.misiones')">{{ __('jota.misiones.titulo') }}</x-ui.boton>
        </x-ui.vacio>
    @else
        <x-ui.lista>
            @foreach ($h['filas'] as $f)
                @php($icono = ['mision' => 'flag', 'asistencia' => 'qr-code', 'cumplimiento' => 'user-group', 'tienda' => 'jota-moneda', 'devolucion' => 'arrow-uturn-left', 'penalizacion' => 'exclamation-triangle', 'anulacion' => 'arrow-uturn-left', 'ajuste' => 'adjustments-horizontal'][$f['origen']])
                <x-ui.fila :titulo="__('jota.historial.origen.'.$f['origen'])" :subtitulo="trim(($f['detalle'] ? $f['detalle'].' · ' : '').cuando($f['cuando']))" :icono="$icono">
                    <x-slot:derecha>
                        <span @class(['numeros text-right font-bold', 'text-exito-texto' => $f['cantidad'] > 0, 'text-error-texto' => $f['cantidad'] < 0])>
                            {{ $f['cantidad'] > 0 ? '+' : '−' }}{{ number_format(abs($f['cantidad']), 0, ',', '.') }}<span class="block text-xs text-texto-suave">{{ $f['tipo'] === 'puntos' ? __('jota.historial.puntos_min') : __('jota.historial.jlis') }}</span>
                        </span>
                    </x-slot:derecha>
                </x-ui.fila>
            @endforeach
        </x-ui.lista>

        @if ($h['hay_mas'])
            <x-ui.boton variante="secundario" wire:click="cargarMas" wire:loading.attr="disabled" wire:target="cargarMas">{{ __('jota.historial.cargar_mas') }}</x-ui.boton>
        @endif
    @endif
</x-ui.pantalla>
