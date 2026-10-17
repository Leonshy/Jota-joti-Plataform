<x-ui.pantalla wire:poll.30s :volver="route('dirigente.inicio')" :titulo="__('jota.contactos.dir_titulo')">
    <x-ui.selector-unidad />

    @if ($filas === [])
        <x-ui.vacio icono="check-circle" :titulo="__('jota.contactos.dir_vacio_titulo')" :texto="__('jota.contactos.dir_vacio_texto')" />
    @else
        <p class="numeros text-2xl font-bold text-titulo">{{ trans_choice('jota.contactos.dir_pendientes', count($filas), ['count' => count($filas)]) }}</p>

        <x-ui.lista>
            @foreach ($filas as $f)
                <x-ui.fila :href="route('dirigente.contacto', $f['id'])" :icono="$f['tipo'] === 'jota' ? 'signal' : 'globe-alt'" :titulo="$f['titulo']" :subtitulo="$f['subtitulo']">
                    <x-slot:derecha><x-ui.insignia tono="marca" :icono="false">{{ __('jota.contactos.'.$f['tipo']) }}</x-ui.insignia></x-slot:derecha>
                </x-ui.fila>
            @endforeach
        </x-ui.lista>
    @endif
</x-ui.pantalla>
