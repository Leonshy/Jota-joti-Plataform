<x-layouts.movil titulo="Historial del puesto">
    <x-ui.pantalla :titulo="__('jota.tienda.historial_titulo')">
        <x-slot:barra><x-ui.barra-tienda activa="historial" /></x-slot:barra>

        <x-ui.tarjeta tono="info" class="text-center">
            <p class="text-sm">{{ __('jota.tienda.total_cobrado') }}</p>
            <p class="numeros text-4xl font-bold text-violeta-900">350 <span class="text-2xl">LisCoins</span></p>
        </x-ui.tarjeta>

        <x-ui.seccion :titulo="__('jota.tienda.cobros')">
            <x-ui.lista>
                <x-ui.fila icono="jota-moneda" titulo="Ana Benítez" subtitulo="15:42"><x-slot:derecha><span class="numeros font-bold">30</span></x-slot:derecha></x-ui.fila>
                <x-ui.fila icono="jota-moneda" titulo="Beto Cardozo" subtitulo="15:30"><x-slot:derecha><span class="numeros font-bold">45</span></x-slot:derecha></x-ui.fila>
                <x-ui.fila icono="clock" titulo="—" subtitulo="15:12"><x-slot:derecha><x-ui.insignia tono="neutro" :icono="false">{{ __('jota.tienda.estado_vencido') }}</x-ui.insignia></x-slot:derecha></x-ui.fila>
            </x-ui.lista>
        </x-ui.seccion>

        <x-ui.seccion :titulo="__('jota.tienda.devoluciones')">
            <x-ui.lista>
                <x-ui.fila icono="arrow-uturn-left" titulo="Ana Benítez" subtitulo="«Producto en mal estado» · 15:50"><x-slot:derecha><span class="numeros font-bold text-exito-texto">+20</span></x-slot:derecha></x-ui.fila>
            </x-ui.lista>
        </x-ui.seccion>
    </x-ui.pantalla>
</x-layouts.movil>
