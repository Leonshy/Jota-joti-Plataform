<x-ui.pantalla wire:poll.30s :volver="route('dirigente.inicio')" :titulo="__('jota.ranking.titulo', ['unidad' => $unidad])">
    <x-slot:barra><x-ui.barra-dirigente activa="cola" modo="app" /></x-slot:barra>

    <x-ui.selector-unidad />

    <p class="text-texto-suave">{{ $vista['modo'] === 'equipo' ? __('jota.ranking.subtitulo_equipo') : __('jota.ranking.subtitulo_individual') }}</p>

    @if ($vista['tabla'] === [])
        <x-ui.vacio icono="trophy" :titulo="__('jota.ranking.vacio_titulo')" :texto="__('jota.ranking.vacio_texto')" />
    @else
        <x-ui.tabla-ranking-nombres :titulo="__('jota.ranking.titulo', ['unidad' => $unidad])" :modo="$vista['modo']" :filas="$vista['tabla']" />
        <p class="text-sm text-texto-suave">{{ __('jota.ranking.empates') }} {{ __('jota.ranking.dirigente_nota') }}</p>
    @endif
</x-ui.pantalla>
