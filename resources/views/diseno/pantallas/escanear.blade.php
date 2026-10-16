@php($estado ??= null)
<x-layouts.movil titulo="Escanear">
    <x-ui.pantalla :titulo="__('jota.escanear.titulo')" :h1-propio="$estado === 'sin-camara'">
        <x-slot:barra><x-ui.barra-participante activa="escanear" /></x-slot:barra>

        @if ($estado === 'sin-camara')
            <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.escanear.sin_camara_titulo')">
                <x-ui.tarjeta class="text-left">
                    <ol class="list-decimal space-y-2 pl-5">
                        <li>{{ __('jota.escanear.sin_camara_paso_1') }}</li>
                        <li>{{ __('jota.escanear.sin_camara_paso_2') }}</li>
                        <li>{{ __('jota.escanear.sin_camara_paso_3') }}</li>
                    </ol>
                </x-ui.tarjeta>
                <x-ui.aviso tono="info" class="text-left">{{ __('jota.escanear.sin_camara_alternativa') }}</x-ui.aviso>
                <x-ui.boton :href="url('/diseno/escanear')" icono="arrow-path">{{ __('jota.comun.reintentar') }}</x-ui.boton>
            </x-ui.resultado>
        @else
            {{-- Marco de lectura. En la app real, acá va la imagen de la cámara. --}}
            <div class="relative grid aspect-square w-full place-items-center overflow-hidden rounded-lg bg-violeta-900" role="img" aria-label="{{ __('jota.escanear.vista_camara') }}">
                <svg viewBox="0 0 100 100" class="absolute inset-6 size-[calc(100%-3rem)] text-info" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 22V10a6 6 0 0 1 6-6h12M78 4h12a6 6 0 0 1 6 6v12M96 78v12a6 6 0 0 1-6 6H78M22 96H10a6 6 0 0 1-6-6V78" />
                </svg>
                <x-heroicon-o-qr-code class="size-20 text-primario-suave opacity-40" aria-hidden="true" />
            </div>
            <p class="text-center text-texto-suave">{{ __('jota.escanear.ayuda') }}</p>

            <x-ui.boton variante="secundario" :href="url('/diseno/escanear?estado=sin-camara')">{{ __('jota.escanear.sin_camara_boton') }}</x-ui.boton>

            <p class="text-center text-sm text-texto-suave">
                {{ __('jota.diseno.vista_previa') }}:
                <a href="{{ url('/diseno/asistencia-ok') }}">asistencia</a> ·
                <a href="{{ url('/diseno/confirmar-pago') }}">pago</a>
            </p>
        @endif
    </x-ui.pantalla>
</x-layouts.movil>
