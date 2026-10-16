<x-layouts.movil titulo="Ranking">
    <x-ui.pantalla :volver="url('/diseno/d-cola')" :titulo="__('jota.ranking.titulo', ['unidad' => 'Tropa'])">
        <x-slot:barra><x-ui.barra-dirigente /></x-slot:barra>
        <p class="text-texto-suave">{{ __('jota.ranking.subtitulo_equipo') }}</p>
        <x-ui.tabla-ranking :titulo="__('jota.ranking.titulo', ['unidad' => 'Tropa'])" :filas="[[1, 140], [2, 130], [3, 95], [4, 0]]" />
        <p class="text-sm text-texto-suave">{{ __('jota.ranking.anonimo') }}</p>
    </x-ui.pantalla>
</x-layouts.movil>
