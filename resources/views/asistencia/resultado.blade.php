{{-- Resultado de escanear el QR de asistencia (desde el escáner de la app o la cámara del sistema). --}}
<x-layouts.movil :titulo="__('jota.dirigente.asistencia_titulo')">
    <x-ui.pantalla :completa="true" :h1-propio="true">
        @switch($estado)
            @case('ok')
                <x-ui.resultado tono="exito" :titulo="__('jota.escanear.asistencia_ok_titulo')" :texto="copia('jota.escanear.asistencia_ok_texto', ['actividad' => $actividad])">
                    <div class="grid grid-cols-2 gap-3 text-left">
                        <x-ui.tarjeta tono="cian">
                            <p class="text-sm">{{ __('jota.escanear.tus_puntos') }}</p>
                            <p class="numeros text-3xl font-bold text-violeta-900"><span data-contar data-desde="{{ $saldoPuntos - $puntos }}" data-hasta="{{ $saldoPuntos }}">{{ $saldoPuntos }}</span></p>
                            @if ($puntos > 0)<p class="numeros text-sm font-bold">+{{ $puntos }}</p>@endif
                        </x-ui.tarjeta>
                        <x-ui.tarjeta tono="rosa">
                            <p class="text-sm">{{ __('jota.escanear.tus_jlis') }}</p>
                            <p class="numeros text-3xl font-bold text-violeta-900"><span data-contar data-desde="{{ $saldoJlis - $puntos }}" data-hasta="{{ $saldoJlis }}">{{ $saldoJlis }}</span></p>
                            @if ($puntos > 0)<p class="numeros text-sm font-bold">+{{ $puntos }}</p>@endif
                        </x-ui.tarjeta>
                    </div>
                    <x-ui.boton :href="route('app.inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
                    <x-ui.boton variante="secundario" :href="route('app.escanear')">{{ __('jota.escanear.escanear_otro') }}</x-ui.boton>
                </x-ui.resultado>
                @break
            @case('ya_registrado')
                <x-ui.resultado tono="info" :animar="false" :titulo="__('jota.escanear.asistencia_ya_titulo')" :texto="__('jota.escanear.asistencia_ya_texto')">
                    <x-ui.boton :href="route('app.inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
                </x-ui.resultado>
                @break
            @case('cerrada')
                <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.escanear.asistencia_cerrada_titulo')" :texto="__('jota.escanear.asistencia_cerrada_texto')">
                    <x-ui.boton :href="route('app.inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
                </x-ui.resultado>
                @break
            @case('no_permitido')
                <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.escanear.asistencia_unidad_titulo')" :texto="__('jota.escanear.asistencia_unidad_texto')">
                    <x-ui.boton :href="route('app.inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
                </x-ui.resultado>
                @break
            @default
                <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.escanear.asistencia_vencido_titulo')" :texto="__('jota.escanear.asistencia_vencido_texto')">
                    <x-ui.boton :href="route('app.escanear')" icono="qr-code">{{ __('jota.escanear.escanear_otro') }}</x-ui.boton>
                </x-ui.resultado>
        @endswitch
    </x-ui.pantalla>
</x-layouts.movil>
