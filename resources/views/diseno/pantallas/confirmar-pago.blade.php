@php($estado ??= null)
<x-layouts.movil titulo="Pagar">
    <x-ui.pantalla :volver="url('/diseno/escanear')" :titulo="__('jota.pago.confirmar_titulo')" :h1-propio="$estado === 'vencido'">
        @if ($estado === 'vencido')
            <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.pago.vencido_titulo')" :texto="__('jota.pago.vencido_texto')">
                <x-ui.boton :href="url('/diseno/inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
            </x-ui.resultado>
        @else
            @php($saldo = $estado === 'saldo' ? 5 : 100)
            <x-ui.tarjeta class="text-center">
                <p class="text-texto-suave">{{ __('jota.pago.vas_a_pagar') }}</p>
                <p class="numeros my-1 text-4xl font-bold text-titulo">30 <span class="text-2xl">LisCoins</span></p>
                <p class="text-texto-suave">{{ __('jota.pago.a_tienda', ['tienda' => 'Tienda JOTA']) }}</p>
            </x-ui.tarjeta>

            <dl class="numeros divide-y divide-borde rounded-md bg-fondo-suave px-4">
                <div class="flex items-center justify-between py-3"><dt class="text-texto-suave">{{ __('jota.pago.vence_en') }}</dt><dd class="font-bold" data-cuenta-regresiva="102">1:42</dd></div>
                <div class="flex items-center justify-between py-3"><dt class="text-texto-suave">{{ __('jota.pago.tu_saldo') }}</dt><dd class="font-bold">{{ $saldo }} LisCoins</dd></div>
                <div class="flex items-center justify-between py-3"><dt class="text-texto-suave">{{ __('jota.pago.te_quedan') }}</dt><dd class="font-bold">{{ max($saldo - 30, 0) }} LisCoins</dd></div>
            </dl>

            @if ($estado === 'saldo')
                <x-ui.aviso tono="error">{{ __('jota.pago.error_saldo', ['faltan' => cantidad(25, 'jli')]) }} {{ __('jota.pago.saldo_ayuda') }}</x-ui.aviso>
                <x-ui.boton :deshabilitado="true">{{ __('jota.pago.pagar') }}</x-ui.boton>
            @else
                <x-ui.boton :href="url('/diseno/pago-ok')" icono="check">{{ __('jota.pago.pagar') }}</x-ui.boton>
            @endif
            <x-ui.boton variante="secundario" :href="url('/diseno/inicio')">{{ __('jota.comun.cancelar') }}</x-ui.boton>
        @endif
    </x-ui.pantalla>
</x-layouts.movil>
