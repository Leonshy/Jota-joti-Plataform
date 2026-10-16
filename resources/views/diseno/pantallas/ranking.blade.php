@php($estado ??= null)
@php($rover = $estado === 'rover')
<x-layouts.movil titulo="Ranking">
    <x-ui.pantalla :titulo="__('jota.ranking.titulo', ['unidad' => $rover ? 'Rovers' : 'Tropa'])">
        <x-slot:barra><x-ui.barra-participante activa="ranking" /></x-slot:barra>

        <p class="text-texto-suave">{{ $rover ? __('jota.ranking.subtitulo_individual') : __('jota.ranking.subtitulo_equipo') }}</p>

        @if ($estado === 'vacio')
            <x-ui.vacio icono="trophy" :titulo="__('jota.ranking.vacio_titulo')" :texto="__('jota.ranking.vacio_texto')" />
        @else
            <x-ui.tabla-ranking :titulo="__('jota.ranking.titulo', ['unidad' => $rover ? 'Rovers' : 'Tropa'])"
                :filas="$rover ? [[1, 80], [1, 80], [3, 30], [4, 5]] : [[1, 140], [2, 130], [3, 95], [4, 0]]" />

            <x-ui.seccion :titulo="__('jota.ranking.tus_puntos')">
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.saldo :etiqueta="__('jota.ranking.los_tuyos')" :valor="$rover ? 30 : 10" icono="star" />
                    @unless ($rover)
                        <x-ui.saldo :etiqueta="__('jota.ranking.de_tu_patrulla')" :valor="100" icono="user-group" />
                    @endunless
                </div>
            </x-ui.seccion>

            <p class="text-sm text-texto-suave">{{ __('jota.ranking.anonimo') }} {{ __('jota.ranking.empates') }}</p>
        @endif
    </x-ui.pantalla>
</x-layouts.movil>
