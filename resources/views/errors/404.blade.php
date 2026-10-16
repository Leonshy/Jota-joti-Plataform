<x-layouts.movil :titulo="__('jota.error.404.titulo')">
    <x-ui.pantalla :completa="true" :h1-propio="true">
        <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.error.404.titulo')" :texto="__('jota.error.404.texto')">
            <x-ui.boton :href="url('/')">{{ __('jota.comun.ir_inicio') }}</x-ui.boton>
            <x-ui.boton variante="secundario" href="javascript:location.reload()">{{ __('jota.error.reintentar') }}</x-ui.boton>
        </x-ui.resultado>
    </x-ui.pantalla>
</x-layouts.movil>
