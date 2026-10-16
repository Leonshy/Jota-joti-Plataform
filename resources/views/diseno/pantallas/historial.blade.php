@php($estado ??= null)
<x-layouts.movil titulo="Historial">
    <x-ui.pantalla :volver="url('/diseno/inicio')" :titulo="__('jota.historial.titulo')">
        @if ($estado === 'vacio')
            <x-ui.vacio icono="clock" :titulo="__('jota.historial.vacio_titulo')" :texto="__('jota.historial.vacio_texto')">
                <x-ui.boton :href="url('/diseno/misiones')">{{ __('jota.misiones.titulo') }}</x-ui.boton>
            </x-ui.vacio>
        @else
            <nav aria-label="{{ __('jota.historial.filtrar') }}" class="flex gap-2">
                <a href="#" aria-current="page" class="grid min-h-tactil place-items-center rounded-full bg-primario px-5 text-sm font-bold text-sobre-primario no-underline">{{ __('jota.historial.todo') }}</a>
                <a href="#" class="grid min-h-tactil place-items-center rounded-full bg-primario-suave px-5 text-sm font-bold text-primario no-underline">{{ __('jota.historial.puntos') }}</a>
                <a href="#" class="grid min-h-tactil place-items-center rounded-full bg-primario-suave px-5 text-sm font-bold text-primario no-underline">{{ __('jota.historial.jlis') }}</a>
            </nav>

            <x-ui.lista>
                @foreach ([
                    ['+20', 'puntos', __('jota.historial.origen.asistencia'), 'Taller de nudos · '.__('jota.comun.hoy').' 15:20', 'qr-code', 'exito'],
                    ['+20', 'LisCoins', __('jota.historial.origen.asistencia'), 'Taller de nudos · '.__('jota.comun.hoy').' 15:20', 'qr-code', 'exito'],
                    ['−30', 'LisCoins', __('jota.historial.origen.tienda'), 'Tienda JOTA · '.__('jota.comun.hoy').' 14:05', 'jota-moneda', 'error'],
                    ['+10', 'puntos', __('jota.historial.origen.mision'), 'Bienvenida al JOTA-JOTI · '.__('jota.comun.hoy').' 11:40', 'flag', 'exito'],
                    ['−5', 'puntos', __('jota.historial.origen.penalizacion'), 'Llegó tarde · '.__('jota.comun.ayer').' 18:10', 'exclamation-triangle', 'error'],
                ] as [$cant, $unidad, $titulo, $detalle, $icono, $tono])
                    <x-ui.fila :titulo="$titulo" :subtitulo="$detalle" :icono="$icono">
                        <x-slot:derecha>
                            <span @class(['numeros text-right font-bold', 'text-exito-texto' => $tono === 'exito', 'text-error-texto' => $tono === 'error'])>
                                {{ $cant }}<span class="block text-xs text-texto-suave">{{ $unidad }}</span>
                            </span>
                        </x-slot:derecha>
                    </x-ui.fila>
                @endforeach
            </x-ui.lista>

            <x-ui.boton variante="secundario">{{ __('jota.historial.cargar_mas') }}</x-ui.boton>
        @endif
    </x-ui.pantalla>
</x-layouts.movil>
