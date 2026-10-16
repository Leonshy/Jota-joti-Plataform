<x-layouts.movil titulo="Asistencia">
    <x-ui.pantalla :titulo="__('jota.dirigente.asistencia_titulo')">
        <x-slot:barra><x-ui.barra-dirigente activa="asistencia" /></x-slot:barra>

        <x-ui.lista>
            <x-ui.fila :href="url('/diseno/d-qr')" icono="qr-code" titulo="Estación JOTA: radioaficionados" :subtitulo="__('jota.actividades.individual').' · '.cantidad(15, 'punto')">
                <x-slot:derecha><x-ui.insignia tono="exito">{{ __('jota.dirigente.asistencia_abierta', ['n' => 12]) }}</x-ui.insignia></x-slot:derecha>
            </x-ui.fila>
            <x-ui.fila :href="url('/diseno/d-qr')" icono="qr-code" titulo="Taller de nudos" :subtitulo="__('jota.actividades.individual').' · '.cantidad(20, 'punto')">
                <x-slot:derecha><x-ui.insignia tono="neutro" :icono="false">{{ __('jota.dirigente.asistencia_sin_sesion') }}</x-ui.insignia></x-slot:derecha>
            </x-ui.fila>
            <x-ui.fila :href="url('/diseno/d-cumplimientos')" icono="user-group" titulo="Gran juego de patrullas" :subtitulo="__('jota.actividades.equipo')">
                <x-slot:derecha><x-ui.insignia tono="marca" :icono="false">{{ __('jota.navegacion.cumplimientos') }}</x-ui.insignia></x-slot:derecha>
            </x-ui.fila>
        </x-ui.lista>

        <x-ui.aviso tono="info">{{ __('jota.dirigente.abrir_ayuda') }}</x-ui.aviso>
    </x-ui.pantalla>
</x-layouts.movil>
