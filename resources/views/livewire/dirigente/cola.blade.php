<x-ui.pantalla wire:poll.30s :titulo="__('jota.dirigente.cola_titulo')">
    <x-slot:barra><x-ui.barra-dirigente activa="cola" modo="app" /></x-slot:barra>

    <x-ui.selector-unidad />

    @if ($filas === [])
        <x-ui.vacio icono="check-circle" :titulo="__('jota.dirigente.cola_vacia_titulo')" :texto="__('jota.dirigente.cola_vacia_texto')" />
    @else
        <div>
            <p class="numeros text-2xl font-bold text-titulo">{{ trans_choice('jota.dirigente.cola_pendientes', count($filas)) }}</p>
            <p class="text-sm text-texto-suave">{{ __('jota.dirigente.cola_orden') }}</p>
        </div>

        <x-ui.lista>
            @foreach ($filas as $f)
                <x-ui.fila :href="route('dirigente.revisar', $f['id'])" :icono="$f['por_patrulla'] ? 'user-group' : ($f['fotos'] ? 'photo' : 'document-text')" :titulo="$f['titulo']" :subtitulo="$f['subtitulo']">
                    <x-slot:derecha><x-ui.insignia :tono="$f['fotos'] ? 'marca' : 'neutro'" :icono="false">{{ $f['fotos'] ? trans_choice('jota.dirigente.n_fotos', $f['fotos']) : __('jota.dirigente.solo_texto') }}</x-ui.insignia></x-slot:derecha>
                </x-ui.fila>
            @endforeach
        </x-ui.lista>
    @endif

    <x-ui.boton variante="secundario" :href="route('dirigente.contactos')" icono="signal">{{ $contactos > 0 ? __('jota.contactos.dir_ver_n', ['n' => $contactos]) : __('jota.contactos.dir_ver') }}</x-ui.boton>

    <div class="flex flex-wrap items-center justify-between gap-2">
        <x-ui.boton variante="enlace" :bloque="false" :href="route('dirigente.ranking')" icono="trophy">{{ __('jota.dirigente.ver_ranking') }}</x-ui.boton>
        <x-ui.boton variante="enlace" :bloque="false" :href="route('dirigente.reservas')" icono="calendar-days">{{ __('jota.dirigente.reservas_ver') }}</x-ui.boton>
        @if (auth()->user()->esAdmin())
            <x-ui.boton variante="enlace" :bloque="false" :href="url('/admin')" icono="cog-6-tooth">{{ __('jota.dirigente.panel') }}</x-ui.boton>
        @endif
        <form method="post" action="{{ route('salir') }}">
            @csrf
            <x-ui.boton type="submit" variante="enlace" :bloque="false" icono="arrow-right-start-on-rectangle">{{ __('jota.comun.cerrar_sesion') }}</x-ui.boton>
        </form>
    </div>
</x-ui.pantalla>
