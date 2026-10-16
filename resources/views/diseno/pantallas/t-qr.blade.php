@php($estado ??= null)
<x-layouts.movil titulo="Cobro">
    <x-ui.pantalla :titulo="__('jota.tienda.cobrar_titulo')" :h1-propio="in_array($estado, ['pagado', 'vencido'], true)">
        <x-slot:barra><x-ui.barra-tienda activa="cobrar" /></x-slot:barra>

        @if ($estado === 'pagado')
            <x-ui.resultado tono="exito" :titulo="__('jota.tienda.pagado_por', ['nombre' => 'Ana Benítez'])" texto="30 LisCoins · 15:42">
                <x-ui.boton :href="url('/diseno/t-cobrar')">{{ __('jota.tienda.nuevo_cobro') }}</x-ui.boton>
                <x-ui.boton variante="secundario" :href="url('/diseno/t-historial')">{{ __('jota.tienda.historial_titulo') }}</x-ui.boton>
            </x-ui.resultado>
        @elseif ($estado === 'vencido')
            <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.tienda.vencido_titulo')" :texto="__('jota.tienda.vencido_texto')">
                <x-ui.boton :href="url('/diseno/t-cobrar')">{{ __('jota.tienda.generar_otro') }}</x-ui.boton>
            </x-ui.resultado>
        @else
            <div class="text-center">
                <p class="numeros text-4xl font-bold text-titulo">30 <span class="text-2xl">LisCoins</span></p>
                <p class="text-texto-suave">{{ __('jota.tienda.qr_titulo') }}</p>
            </div>

            <x-ui.qr :svg="$qr" />

            <div class="text-center">
                <p class="numeros text-3xl font-bold" data-cuenta-regresiva="118">1:58</p>
                <p class="text-texto-suave" role="status">{{ __('jota.tienda.qr_espera') }}</p>
            </div>

            <x-ui.boton variante="secundario" :href="url('/diseno/t-cobrar')">{{ __('jota.tienda.cancelar_cobro') }}</x-ui.boton>
            <p class="text-center text-sm text-texto-suave">
                {{ __('jota.diseno.vista_previa') }}: <a href="{{ url('/diseno/t-qr?estado=pagado') }}">pagado</a> · <a href="{{ url('/diseno/t-qr?estado=vencido') }}">vencido</a>
            </p>
        @endif
    </x-ui.pantalla>
</x-layouts.movil>
