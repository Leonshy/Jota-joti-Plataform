@php($estado ??= null)
<x-layouts.movil titulo="Ingresar">
    <x-ui.pantalla>
        <div class="flex flex-col items-center pt-6">
            <x-ui.logo :ancho="240" />
        </div>

        <div class="text-center">
            <h1 class="text-2xl text-titulo">{{ __('jota.acceso.ingresar_titulo') }}</h1>
            <p class="mt-1 text-texto-suave">{{ __('jota.acceso.ingresar_subtitulo') }}</p>
        </div>

        <form action="#" method="post" class="flex flex-col gap-4">
            <x-ui.campo name="ci" :label="__('jota.acceso.ci_label')" valor="{{ match ($estado) { 'ci' => '7654321', 'pin', 'sin-pin' => '1234567', default => '' } }}" inputmode="numeric" autocomplete="username"
                :ayuda="$estado === 'ci' ? null : __('jota.acceso.ci_ayuda')"
                :error="match ($estado) { 'ci' => __('jota.acceso.error_ci'), 'sin-pin' => __('jota.acceso.error_sin_pin'), default => null }" />
            <x-ui.campo name="pin" :label="__('jota.acceso.pin_label')" :pin="true" :valor="$estado === 'pin' ? '0000' : null" :error="$estado === 'pin' ? __('jota.acceso.error_pin') : null" />

            <x-ui.boton type="submit">{{ __('jota.acceso.ingresar_boton') }}</x-ui.boton>
            <x-ui.boton variante="secundario" :href="url('/diseno/activar')">{{ __('jota.acceso.primera_vez') }}</x-ui.boton>
            <x-ui.boton variante="enlace" :href="url('/diseno/ingresar?estado=ayuda')" :bloque="false" class="mx-auto">{{ __('jota.acceso.olvido_pin') }}</x-ui.boton>
        </form>

        @if ($estado === 'ayuda')
            <x-ui.aviso tono="info" :titulo="__('jota.acceso.ayuda_titulo')">
                <ol class="mt-1 list-decimal space-y-1 pl-5">
                    <li>{{ __('jota.acceso.ayuda_paso_1') }}</li>
                    <li>{{ __('jota.acceso.ayuda_paso_2') }}</li>
                </ol>
            </x-ui.aviso>
        @endif
    </x-ui.pantalla>
</x-layouts.movil>
