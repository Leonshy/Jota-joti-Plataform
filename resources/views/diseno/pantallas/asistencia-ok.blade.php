@php($estado ??= null)
<x-layouts.movil titulo="Asistencia">
    <x-ui.pantalla>
        @switch($estado)
            @case('vencido')
                <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.escanear.asistencia_vencido_titulo')" :texto="__('jota.escanear.asistencia_vencido_texto')">
                    <x-ui.boton :href="url('/diseno/escanear')" icono="qr-code">{{ __('jota.escanear.escanear_otro') }}</x-ui.boton>
                </x-ui.resultado>
                @break
            @case('ya')
                <x-ui.resultado tono="info" :animar="false" :titulo="__('jota.escanear.asistencia_ya_titulo')" :texto="__('jota.escanear.asistencia_ya_texto')">
                    <x-ui.boton :href="url('/diseno/inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
                </x-ui.resultado>
                @break
            @default
                <x-ui.resultado tono="exito" :titulo="__('jota.escanear.asistencia_ok_titulo')" :texto="copia('jota.escanear.asistencia_ok_texto', ['actividad' => 'Taller de nudos'])">
                    <div class="grid grid-cols-2 gap-3 text-left">
                        <x-ui.tarjeta tono="cian">
                            <p class="text-sm">{{ __('jota.escanear.tus_puntos') }}</p>
                            <p class="numeros text-3xl font-bold text-violeta-900"><span data-contar data-desde="10" data-hasta="30">30</span></p>
                            <p class="numeros text-sm font-bold">+20</p>
                        </x-ui.tarjeta>
                        <x-ui.tarjeta tono="rosa">
                            <p class="text-sm">{{ __('jota.escanear.tus_jlis') }}</p>
                            <p class="numeros text-3xl font-bold text-violeta-900"><span data-contar data-desde="70" data-hasta="90">90</span></p>
                            <p class="numeros text-sm font-bold">+20</p>
                        </x-ui.tarjeta>
                    </div>
                    <x-ui.boton :href="url('/diseno/inicio')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
                    <x-ui.boton variante="secundario" :href="url('/diseno/escanear')">{{ __('jota.escanear.escanear_otro') }}</x-ui.boton>
                </x-ui.resultado>
        @endswitch
    </x-ui.pantalla>
</x-layouts.movil>
