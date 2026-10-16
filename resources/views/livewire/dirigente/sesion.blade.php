<x-ui.pantalla wire:poll.5s :volver="route('dirigente.asistencia')" :titulo="__('jota.dirigente.asistencia_titulo')" :h1-propio="true">
    @if (! $abierta)
        <x-ui.resultado tono="exito" :titulo="__('jota.dirigente.sesion_cerrada_titulo')" :texto="trans_choice('jota.dirigente.sesion_cerrada_texto', $asistentes, ['n' => $asistentes])">
            <x-ui.boton :href="route('dirigente.asistencia')">{{ __('jota.dirigente.volver_asistencia') }}</x-ui.boton>
        </x-ui.resultado>
    @else
        <div>
            <h1 class="text-2xl text-titulo">{{ $titulo }}</h1>
            <p class="text-texto-suave">{{ __('jota.dirigente.sesion_abierta') }}</p>
        </div>

        <x-ui.qr :svg="$qr" />
        <p class="text-center text-sm text-texto-suave">{{ __('jota.dirigente.qr_cambia') }}</p>

        <x-ui.saldo tono="cian" :etiqueta="__('jota.dirigente.asistentes')" :valor="$asistentes" icono="user-group" />

        <x-ui.boton :href="$urlPublica" icono="arrows-pointing-out">{{ __('jota.dirigente.mostrar_qr') }}</x-ui.boton>

        @if ($confirmandoCierre)
            <div role="alertdialog" aria-labelledby="cerrar-titulo" class="flex flex-col gap-3 rounded-md bg-error-fondo p-4">
                <p id="cerrar-titulo" class="font-bold">{{ __('jota.dirigente.cerrar_pregunta') }}</p>
                <p class="text-sm">{{ __('jota.dirigente.cerrar_texto') }}</p>
                <x-ui.boton variante="peligro" wire:click="cerrar" wire:loading.attr="disabled" wire:target="cerrar">{{ __('jota.dirigente.cerrar_si') }}</x-ui.boton>
                <x-ui.boton variante="secundario" wire:click="seguirAbierta">{{ __('jota.dirigente.cerrar_no') }}</x-ui.boton>
            </div>
        @else
            <x-ui.boton variante="secundario" wire:click="pedirCierre">{{ __('jota.dirigente.cerrar_sesion') }}</x-ui.boton>
        @endif
    @endif
</x-ui.pantalla>
