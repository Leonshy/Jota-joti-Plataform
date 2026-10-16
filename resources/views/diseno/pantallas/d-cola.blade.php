@php($estado ??= null)
<x-layouts.movil titulo="Cola de validación">
    <x-ui.pantalla :titulo="__('jota.dirigente.cola_titulo')">
        <x-slot:barra><x-ui.barra-dirigente activa="cola" /></x-slot:barra>

        @if ($estado === 'vacia')
            <x-ui.vacio icono="check-circle" :titulo="__('jota.dirigente.cola_vacia_titulo')" :texto="__('jota.dirigente.cola_vacia_texto')" />
        @else
            <div>
                <p class="numeros text-2xl font-bold text-titulo">{{ trans_choice('jota.dirigente.cola_pendientes', 7) }}</p>
                <p class="text-sm text-texto-suave">{{ __('jota.dirigente.cola_orden') }}</p>
            </div>

            <x-ui.lista>
                <x-ui.fila :href="url('/diseno/d-revisar')" icono="photo" titulo="Ana Benítez" subtitulo="Águilas · Bienvenida al JOTA-JOTI · hace 12 min">
                    <x-slot:derecha><x-ui.insignia tono="marca" :icono="false">2 fotos</x-ui.insignia></x-slot:derecha>
                </x-ui.fila>
                <x-ui.fila :href="url('/diseno/d-revisar')" icono="document-text" titulo="Beto Cardozo" subtitulo="Pumas · Mensaje en clave · hace 9 min">
                    <x-slot:derecha><x-ui.insignia tono="neutro" :icono="false">texto</x-ui.insignia></x-slot:derecha>
                </x-ui.fila>
                <x-ui.fila :href="url('/diseno/d-revisar?estado=patrulla')" icono="user-group" titulo="Patrulla Zorros" subtitulo="Nudos en equipo · hace 3 min">
                    <x-slot:derecha><x-ui.insignia tono="marca" :icono="false">4 fotos</x-ui.insignia></x-slot:derecha>
                </x-ui.fila>
            </x-ui.lista>

            <x-ui.boton variante="enlace" :bloque="false" :href="url('/diseno/d-ranking')" icono="trophy" class="self-start">{{ __('jota.dirigente.ver_ranking') }}</x-ui.boton>
        @endif
    </x-ui.pantalla>
</x-layouts.movil>
