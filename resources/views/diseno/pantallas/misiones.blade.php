<x-layouts.movil titulo="Misiones">
    <x-ui.pantalla :titulo="__('jota.misiones.titulo')">
        <x-slot:barra><x-ui.barra-participante activa="misiones" /></x-slot:barra>

        <x-ui.lista>
            <x-ui.fila :href="url('/diseno/mision?estado=patrulla')" icono="user-group" titulo="Nudos en equipo" :subtitulo="__('jota.misiones.patrulla').' · '.cantidad(50, 'punto')">
                <x-slot:derecha><x-ui.insignia tono="aviso">{{ __('jota.misiones.estados.pendiente') }}</x-ui.insignia></x-slot:derecha>
            </x-ui.fila>
            <x-ui.fila :href="url('/diseno/mision')" icono="flag" titulo="Bienvenida al JOTA-JOTI" :subtitulo="__('jota.misiones.individual').' · '.cantidad(10, 'punto')">
                <x-slot:derecha><x-ui.insignia tono="marca">{{ __('jota.misiones.estados.nueva') }}</x-ui.insignia></x-slot:derecha>
            </x-ui.fila>
            <x-ui.fila :href="url('/diseno/mision?estado=rechazada')" icono="flag" titulo="Mensaje en clave" :subtitulo="__('jota.misiones.individual').' · '.cantidad(30, 'punto')">
                <x-slot:derecha><x-ui.insignia tono="error">{{ __('jota.misiones.estados.rechazada') }}</x-ui.insignia></x-slot:derecha>
            </x-ui.fila>
            <x-ui.fila :href="url('/diseno/mision?estado=aprobada')" icono="flag" titulo="Armá tu mochila" :subtitulo="__('jota.misiones.individual').' · '.cantidad(15, 'punto')">
                <x-slot:derecha><x-ui.insignia tono="exito">{{ __('jota.misiones.estados.aprobada') }}</x-ui.insignia></x-slot:derecha>
            </x-ui.fila>
            <x-ui.fila :href="url('/diseno/mision?estado=vencida')" icono="flag" titulo="Desafío de la mañana" :subtitulo="__('jota.misiones.individual').' · '.cantidad(20, 'punto')">
                <x-slot:derecha><x-ui.insignia tono="neutro">{{ __('jota.misiones.estados.vencida') }}</x-ui.insignia></x-slot:derecha>
            </x-ui.fila>
        </x-ui.lista>
    </x-ui.pantalla>
</x-layouts.movil>
