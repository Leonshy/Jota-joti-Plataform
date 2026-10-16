{{-- Resultado de pagar (o de abrir) un cobro: pago listo, ya pagado, vencido, que no existe o sin saldo. --}}
<x-layouts.movil :titulo="__('jota.pago.confirmar_titulo')">
    <x-ui.pantalla :completa="true" :h1-propio="true">
        @switch($estado)
            @case('ok')
                <x-ui.resultado tono="exito" :titulo="__('jota.pago.ok_titulo')" :texto="copia('jota.pago.ok_texto', ['monto' => cantidad($monto, 'jli'), 'tienda' => $tienda])">
                    <x-ui.tarjeta class="flex items-center justify-between">
                        <span class="text-texto-suave">{{ __('jota.pago.te_quedan') }}</span>
                        <span class="numeros text-2xl font-bold text-titulo"><span data-contar data-desde="{{ $saldoAntes }}" data-hasta="{{ $saldo }}" data-duracion="550">{{ $saldo }}</span> {{ trans_choice('jota.unidad.jli', $saldo) }}</span>
                    </x-ui.tarjeta>
                    <x-ui.boton :href="route('app.inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
                    <x-ui.boton variante="secundario" :href="route('app.historial')">{{ __('jota.pago.ver_historial') }}</x-ui.boton>
                </x-ui.resultado>
                @break
            @case('saldo')
                <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.pago.saldo_titulo')" :texto="__('jota.pago.error_saldo', ['faltan' => cantidad($faltan, 'jli')]).' '.__('jota.pago.saldo_ayuda')">
                    <x-ui.boton :href="route('cobro.ver', $token)" icono="arrow-path">{{ __('jota.comun.reintentar') }}</x-ui.boton>
                    <x-ui.boton variante="secundario" :href="route('app.inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
                </x-ui.resultado>
                @break
            @case('ya_pagado')
                <x-ui.resultado tono="info" :animar="false" :titulo="__('jota.pago.ya_pagado_titulo')" :texto="__('jota.pago.ya_pagado_texto')">
                    <x-ui.boton :href="route('app.inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
                </x-ui.resultado>
                @break
            @case('vencido')
                <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.pago.vencido_titulo')" :texto="__('jota.pago.vencido_texto')">
                    <x-ui.boton :href="route('app.inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
                </x-ui.resultado>
                @break
            @default
                <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.pago.no_existe_titulo')" :texto="__('jota.pago.no_existe_texto')">
                    <x-ui.boton :href="route('app.inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
                </x-ui.resultado>
        @endswitch
    </x-ui.pantalla>
</x-layouts.movil>
