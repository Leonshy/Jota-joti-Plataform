<x-ui.pantalla wire:poll.30s :titulo="__('jota.ranking.titulo', ['unidad' => $unidad])">
    <x-slot:barra><x-ui.barra-participante activa="ranking" modo="app" /></x-slot:barra>

    <p class="text-texto-suave">{{ $vista['modo'] === 'equipo' ? __('jota.ranking.subtitulo_equipo') : __('jota.ranking.subtitulo_individual') }}</p>

    @if ($vista['tabla'] === [])
        <x-ui.vacio icono="trophy" :titulo="__('jota.ranking.vacio_titulo')" :texto="__('jota.ranking.vacio_texto')" />
    @else
        <x-ui.tabla-ranking :titulo="__('jota.ranking.titulo', ['unidad' => $unidad])"
            :filas="array_map(fn ($f) => [$f['posicion'], $f['puntaje']], $vista['tabla'])" />

        <x-ui.seccion :titulo="__('jota.ranking.tus_puntos')">
            <div class="grid grid-cols-2 gap-3">
                <x-ui.saldo :etiqueta="__('jota.ranking.los_tuyos')" :valor="$vista['mis_puntos']" icono="star" />
                @if ($vista['puntos_patrulla'] !== null)
                    <x-ui.saldo :etiqueta="__('jota.ranking.de_tu_patrulla')" :valor="$vista['puntos_patrulla']" icono="user-group" />
                @endif
            </div>
        </x-ui.seccion>

        <p class="text-sm text-texto-suave">{{ __('jota.ranking.anonimo') }} {{ __('jota.ranking.empates') }}</p>
    @endif
</x-ui.pantalla>
