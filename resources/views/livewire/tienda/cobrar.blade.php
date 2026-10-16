<x-ui.pantalla :titulo="__('jota.tienda.cobrar_titulo')" :h1-propio="in_array($estado, ['pagado', 'vencido'], true)">
    <x-slot:barra><x-ui.barra-tienda activa="cobrar" modo="app" /></x-slot:barra>

    {{-- Mientras el cobro espera, la pantalla pregunta cada 2 s si ya lo pagaron. --}}
    <div class="contents" @if ($estado === 'pendiente') wire:poll.2s @endif></div>

    @if ($estado === 'pagado')
        <x-ui.resultado tono="exito" :titulo="__('jota.tienda.pagado_por', ['nombre' => $pagadoPor])" :texto="cantidad($montoCobro, 'jli').' · '.$pagadoA->format('H:i')">
            <x-ui.boton wire:click="nuevo">{{ __('jota.tienda.nuevo_cobro') }}</x-ui.boton>
            <x-ui.boton variante="secundario" :href="route('tienda.historial')">{{ __('jota.tienda.historial_titulo') }}</x-ui.boton>
        </x-ui.resultado>
    @elseif ($estado === 'vencido')
        <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.tienda.vencido_titulo')" :texto="__('jota.tienda.vencido_texto')">
            <x-ui.boton wire:click="nuevo">{{ __('jota.tienda.generar_otro') }}</x-ui.boton>
        </x-ui.resultado>
    @elseif ($estado === 'pendiente')
        <div class="text-center">
            <p class="numeros text-4xl font-bold text-titulo">{{ number_format($montoCobro, 0, ',', '.') }} <span class="text-2xl">{{ trans_choice('jota.unidad.jli', $montoCobro) }}</span></p>
            <p class="text-texto-suave">{{ __('jota.tienda.qr_titulo') }}</p>
        </div>

        <x-ui.qr :svg="$qr" />

        <div class="text-center">
            {{-- La cuenta regresiva la mueve el navegador; wire:ignore evita que cada refresco la pise. --}}
            <p class="numeros text-3xl font-bold" wire:ignore data-cuenta-regresiva="{{ $segundos }}">{{ intdiv($segundos, 60) }}:{{ str_pad($segundos % 60, 2, '0', STR_PAD_LEFT) }}</p>
            <p class="text-texto-suave" role="status">{{ __('jota.tienda.qr_espera') }}</p>
        </div>

        <x-ui.boton variante="secundario" wire:click="cancelar" wire:loading.attr="disabled" wire:target="cancelar">{{ __('jota.tienda.cancelar_cobro') }}</x-ui.boton>
    @else
        <form wire:submit="generar" class="flex flex-col gap-5" novalidate>
            <x-ui.tarjeta class="text-center">
                <p class="text-sm text-texto-suave">{{ __('jota.tienda.monto_label') }}</p>
                <p class="numeros mt-1 text-4xl font-bold text-titulo"><span data-monto>{{ $monto === '' ? '0' : number_format((int) $monto, 0, ',', '.') }}</span> <span class="text-2xl" data-monto-unidad data-singular="{{ trans_choice('jota.unidad.jli', 1) }}" data-plural="{{ trans_choice('jota.unidad.jli', 2) }}">{{ trans_choice('jota.unidad.jli', (int) $monto) }}</span></p>
                @if ($error)<p class="mt-2 text-sm font-bold text-error-texto" role="alert">{{ $error }}</p>@endif
            </x-ui.tarjeta>
            <input type="hidden" data-monto-valor wire:model="monto" value="{{ $monto }}">

            <x-ui.teclado />

            <x-ui.boton type="submit" icono="qr-code" wire:loading.attr="disabled" wire:target="generar">{{ __('jota.tienda.generar') }}</x-ui.boton>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-2">
            @if (auth()->user()->esAdmin())
                <x-ui.boton variante="enlace" :bloque="false" :href="url('/admin')" icono="cog-6-tooth">{{ __('jota.dirigente.panel') }}</x-ui.boton>
            @endif
            <form method="post" action="{{ route('salir') }}">
                @csrf
                <x-ui.boton type="submit" variante="enlace" :bloque="false" icono="arrow-right-start-on-rectangle">{{ __('jota.comun.cerrar_sesion') }}</x-ui.boton>
            </form>
        </div>
    @endif
</x-ui.pantalla>
