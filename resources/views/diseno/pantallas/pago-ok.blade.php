<x-layouts.movil titulo="Pago listo">
    <x-ui.pantalla>
        <x-ui.resultado tono="exito" :titulo="__('jota.pago.ok_titulo')" :texto="copia('jota.pago.ok_texto', ['monto' => cantidad(30, 'jli'), 'tienda' => 'Tienda JOTA'])">
            <x-ui.tarjeta class="flex items-center justify-between">
                <span class="text-texto-suave">{{ __('jota.pago.te_quedan') }}</span>
                <span class="numeros text-2xl font-bold text-titulo"><span data-contar data-desde="100" data-hasta="70" data-duracion="550">70</span> LisCoins</span>
            </x-ui.tarjeta>
            <x-ui.boton :href="url('/diseno/inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
            <x-ui.boton variante="secundario" :href="url('/diseno/historial')">{{ __('jota.pago.ver_historial') }}</x-ui.boton>
        </x-ui.resultado>
    </x-ui.pantalla>
</x-layouts.movil>
