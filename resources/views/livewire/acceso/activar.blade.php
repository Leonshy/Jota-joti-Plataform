<x-ui.pantalla :volver="route('ingresar')" :titulo="__('jota.acceso.activar_titulo')">
    @if ($paso === 1)
        <p class="text-texto-suave">{{ __('jota.acceso.activar_paso_ci') }}</p>

        <form wire:submit="continuar" class="flex flex-col gap-4" novalidate>
            <x-ui.campo name="ci" wire:model="ci" :label="__('jota.acceso.ci_label')" inputmode="numeric" autocomplete="username" autofocus
                :ayuda="$errors->has('ci') ? null : __('jota.acceso.activar_ci_ayuda')" :error="$errors->first('ci') ?: null" />

            <x-ui.boton type="submit" wire:loading.attr="disabled" wire:target="continuar">{{ __('jota.comun.continuar') }}</x-ui.boton>
            <x-ui.boton variante="secundario" :href="route('ingresar')">{{ __('jota.acceso.activar_ya_tengo') }}</x-ui.boton>
        </form>
    @else
        <p class="text-texto-suave">{{ __('jota.acceso.activar_paso_pin') }}</p>

        <form wire:submit="crear" class="flex flex-col gap-4" novalidate>
            <x-ui.campo name="pin" wire:model="pin" :label="__('jota.acceso.activar_pin_label')" :pin="true" autofocus :error="$errors->first('pin') ?: null" />
            <x-ui.campo name="pinRepetido" wire:model="pinRepetido" :label="__('jota.acceso.activar_pin_repetir')" :pin="true" :error="$errors->first('pinRepetido') ?: null" />

            <x-ui.boton type="submit" wire:loading.attr="disabled" wire:target="crear">{{ __('jota.acceso.activar_boton') }}</x-ui.boton>
            <x-ui.boton variante="secundario" type="button" wire:click="volver">{{ __('jota.comun.volver') }}</x-ui.boton>
        </form>
    @endif
</x-ui.pantalla>
