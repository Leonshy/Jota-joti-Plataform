<x-layouts.movil titulo="Cobrar">
    <x-ui.pantalla :titulo="__('jota.tienda.cobrar_titulo')">
        <x-slot:barra><x-ui.barra-tienda activa="cobrar" /></x-slot:barra>

        <x-ui.tarjeta class="text-center">
            <p class="text-sm text-texto-suave">{{ __('jota.tienda.monto_label') }}</p>
            <p class="numeros mt-1 text-4xl font-bold text-titulo"><span data-monto>0</span> <span class="text-2xl">LisCoins</span></p>
        </x-ui.tarjeta>

        <x-ui.teclado />

        <x-ui.boton :href="url('/diseno/t-qr')" icono="qr-code">{{ __('jota.tienda.generar') }}</x-ui.boton>
    </x-ui.pantalla>
</x-layouts.movil>
