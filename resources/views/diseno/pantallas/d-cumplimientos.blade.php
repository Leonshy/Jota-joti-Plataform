<x-layouts.movil titulo="Cumplimientos">
    <x-ui.pantalla :titulo="__('jota.dirigente.cumplimientos_titulo')" :h1-propio="true">
        <x-slot:barra><x-ui.barra-dirigente activa="cumplimientos" /></x-slot:barra>

        <div>
            <h1 class="text-xl text-titulo">Gran juego de patrullas</h1>
            <p class="text-texto-suave">{{ cantidad(60, 'punto') }} a la patrulla</p>
        </div>

        <ul class="flex flex-col gap-3">
            <li><x-ui.tarjeta>
                <div class="flex items-center justify-between gap-3"><p class="font-bold">Águilas</p><x-ui.insignia tono="neutro" :icono="false">{{ __('jota.dirigente.sin_marcar') }}</x-ui.insignia></div>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <x-ui.boton variante="secundario" class="min-h-tactil! text-sm!">{{ __('jota.dirigente.no_cumplida') }}</x-ui.boton>
                    <x-ui.boton class="min-h-tactil! text-sm!">{{ __('jota.dirigente.cumplida') }}</x-ui.boton>
                </div>
            </x-ui.tarjeta></li>
            <li><x-ui.tarjeta>
                <div class="flex items-center justify-between gap-3"><p class="font-bold">Pumas</p><x-ui.insignia tono="exito">{{ __('jota.dirigente.cumplida') }}</x-ui.insignia></div>
                <p class="mt-1 text-sm text-texto-suave">{{ __('jota.dirigente.cumplida_definitiva') }}</p>
            </x-ui.tarjeta></li>
            <li><x-ui.tarjeta>
                <div class="flex items-center justify-between gap-3"><p class="font-bold">Zorros</p><x-ui.insignia tono="error">{{ __('jota.dirigente.no_cumplida') }}</x-ui.insignia></div>
                <x-ui.boton variante="secundario" class="mt-3 min-h-tactil! text-sm!">{{ __('jota.dirigente.corregir_cumplida') }}</x-ui.boton>
            </x-ui.tarjeta></li>
        </ul>

        <x-ui.aviso tono="aviso" :titulo="__('jota.dirigente.cumplida_pregunta', ['nombre' => 'Águilas'])">
            {{ copia('jota.dirigente.cumplida_aviso', ['puntos' => cantidad(60, 'punto'), 'jlis' => cantidad(15, 'jli')]) }}
        </x-ui.aviso>
    </x-ui.pantalla>
</x-layouts.movil>
