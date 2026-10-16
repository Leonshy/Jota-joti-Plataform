@php($estado ??= null)
<x-layouts.movil titulo="Creá tu PIN">
    <x-ui.pantalla :volver="url('/diseno/ingresar')" :titulo="__('jota.acceso.activar_titulo')">
        @if (in_array($estado, ['pin', 'pin-error'], true))
            <p class="text-texto-suave">{{ __('jota.acceso.activar_paso_pin') }}</p>
            <form action="#" method="post" class="flex flex-col gap-4">
                <x-ui.campo name="pin1" :label="__('jota.acceso.activar_pin_label')" :pin="true" />
                <x-ui.campo name="pin2" :label="__('jota.acceso.activar_pin_repetir')" :pin="true"
                    :error="$estado === 'pin-error' ? __('jota.acceso.error_pin_distinto') : null" />
                <x-ui.boton type="submit">{{ __('jota.acceso.activar_boton') }}</x-ui.boton>
            </form>
        @else
            <p class="text-texto-suave">{{ __('jota.acceso.activar_paso_ci') }}</p>
            <form action="{{ url('/diseno/activar?estado=pin') }}" method="get" class="flex flex-col gap-4">
                <input type="hidden" name="estado" value="pin">
                <x-ui.campo name="ci" :label="__('jota.acceso.ci_label')" inputmode="numeric" autocomplete="username"
                    :ayuda="$estado === 'ci' ? null : __('jota.acceso.activar_ci_ayuda')"
                    :error="match ($estado) { 'ci' => __('jota.acceso.error_ci'), 'ya' => __('jota.acceso.error_ya_activada'), default => null }" />
                <x-ui.boton type="submit">{{ __('jota.comun.continuar') }}</x-ui.boton>
                <x-ui.boton variante="secundario" :href="url('/diseno/ingresar')">{{ __('jota.acceso.activar_ya_tengo') }}</x-ui.boton>
            </form>
        @endif
    </x-ui.pantalla>
</x-layouts.movil>
