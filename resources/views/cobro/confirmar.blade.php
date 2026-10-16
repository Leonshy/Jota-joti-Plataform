{{-- Se abre al escanear el QR de cobro de la tienda. Pagar es un envío común: funciona aunque falle el JavaScript. --}}
<x-layouts.movil :titulo="__('jota.pago.confirmar_titulo')">
    <x-ui.pantalla :volver="route('app.inicio')" :titulo="__('jota.pago.confirmar_titulo')">
        @php($alcanza = $saldo >= $cobro['monto'])

        <x-ui.tarjeta class="text-center">
            <p class="text-texto-suave">{{ __('jota.pago.vas_a_pagar') }}</p>
            <p class="numeros my-1 text-4xl font-bold text-titulo">{{ number_format($cobro['monto'], 0, ',', '.') }} <span class="text-2xl">{{ trans_choice('jota.unidad.jli', $cobro['monto']) }}</span></p>
            <p class="text-texto-suave">{{ __('jota.pago.a_tienda', ['tienda' => $cobro['tienda']]) }}</p>
        </x-ui.tarjeta>

        <dl class="numeros divide-y divide-borde rounded-md bg-fondo-suave px-4">
            <div class="flex items-center justify-between py-3"><dt class="text-texto-suave">{{ __('jota.pago.vence_en') }}</dt><dd class="font-bold" data-cuenta-regresiva="{{ $cobro['segundos'] }}">{{ intdiv($cobro['segundos'], 60) }}:{{ str_pad($cobro['segundos'] % 60, 2, '0', STR_PAD_LEFT) }}</dd></div>
            <div class="flex items-center justify-between py-3"><dt class="text-texto-suave">{{ __('jota.pago.tu_saldo') }}</dt><dd class="font-bold">{{ cantidad($saldo, 'jli') }}</dd></div>
            <div class="flex items-center justify-between py-3"><dt class="text-texto-suave">{{ __('jota.pago.te_quedan') }}</dt><dd class="font-bold">{{ cantidad(max($saldo - $cobro['monto'], 0), 'jli') }}</dd></div>
        </dl>

        @if (! $alcanza)
            <x-ui.aviso tono="error">{{ __('jota.pago.error_saldo', ['faltan' => cantidad($cobro['monto'] - $saldo, 'jli')]) }} {{ __('jota.pago.saldo_ayuda') }}</x-ui.aviso>
            <x-ui.boton :deshabilitado="true">{{ __('jota.pago.pagar') }}</x-ui.boton>
            <x-ui.boton variante="secundario" :href="route('cobro.ver', $cobro['token'])" icono="arrow-path">{{ __('jota.comun.reintentar') }}</x-ui.boton>
        @else
            <form method="post" action="{{ route('cobro.pagar', $cobro['token']) }}" onsubmit="this.querySelector('button').disabled = true">
                @csrf
                <x-ui.boton type="submit" icono="check">{{ __('jota.pago.pagar') }}</x-ui.boton>
            </form>
        @endif
        <x-ui.boton variante="secundario" :href="route('app.inicio')">{{ __('jota.comun.cancelar') }}</x-ui.boton>
    </x-ui.pantalla>
</x-layouts.movil>
