<x-layouts.movil :titulo="__('jota.escanear.titulo')">
    <x-ui.pantalla :titulo="__('jota.escanear.titulo')" data-escaner>
        <x-slot:barra><x-ui.barra-participante activa="escanear" modo="app" /></x-slot:barra>

        <div data-escaner-camara class="flex flex-col gap-5">
            <div class="relative grid aspect-square w-full place-items-center overflow-hidden rounded-lg bg-violeta-900">
                <video data-escaner-video class="absolute inset-0 size-full object-cover" playsinline muted autoplay aria-label="{{ __('jota.escanear.vista_camara') }}"></video>
                <svg viewBox="0 0 100 100" class="absolute inset-6 size-[calc(100%-3rem)] text-info" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 22V10a6 6 0 0 1 6-6h12M78 4h12a6 6 0 0 1 6 6v12M96 78v12a6 6 0 0 1-6 6H78M22 96H10a6 6 0 0 1-6-6V78" />
                </svg>
            </div>
            <p data-escaner-aviso role="status" data-leyendo="{{ __('jota.escanear.leyendo') }}" data-no-reconocido="{{ __('jota.escanear.no_reconocido_aviso') }}" class="text-center text-texto-suave">{{ __('jota.escanear.ayuda') }}</p>
        </div>

        <div data-escaner-sin-camara hidden>
            <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.escanear.sin_camara_titulo')">
                @foreach (['inseguro', 'denegado', 'sin-camara', 'ocupada', 'otro'] as $motivo)
                    <x-ui.aviso tono="{{ $motivo === 'inseguro' ? 'info' : 'aviso' }}" class="text-left" data-motivo="{{ $motivo }}" hidden>{{ __('jota.escanear.motivo_'.str_replace('-', '_', $motivo)) }}</x-ui.aviso>
                @endforeach

                {{-- Los pasos dependen del navegador: se muestran solo los de quien está mirando (los elige el JS). --}}
                @foreach (__('jota.escanear.pasos') as $navegador => $pasos)
                    <x-ui.tarjeta class="text-left" data-navegador="{{ $navegador }}" data-motivo="denegado" hidden>
                        <ol class="list-decimal space-y-2 pl-5">
                            @foreach ($pasos as $paso)<li>{{ $paso }}</li>@endforeach
                        </ol>
                    </x-ui.tarjeta>
                @endforeach

                <x-ui.aviso tono="info" class="text-left" data-motivo="denegado" hidden>{{ __('jota.escanear.sin_camara_alternativa') }}</x-ui.aviso>
                <x-ui.boton :href="route('app.escanear')" icono="arrow-path">{{ __('jota.comun.reintentar') }}</x-ui.boton>
            </x-ui.resultado>
        </div>
    </x-ui.pantalla>
</x-layouts.movil>
