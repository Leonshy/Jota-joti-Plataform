<x-ui.pantalla>
    <div class="flex flex-col items-center pt-6">
        <x-ui.logo :ancho="240" />
    </div>

    <div class="text-center">
        <h1 class="text-2xl">{{ __('jota.acceso.ingresar_titulo') }}</h1>
        <p class="mt-1 text-texto-suave">{{ __('jota.acceso.ingresar_subtitulo') }}</p>
    </div>

    <form wire:submit="ingresar" class="flex flex-col gap-4" novalidate>
        <x-ui.campo name="ci" wire:model="ci" :label="__('jota.acceso.ci_label')" inputmode="numeric" autocomplete="username" autofocus
            :ayuda="$errors->has('ci') ? null : __('jota.acceso.ci_ayuda')" :error="$errors->first('ci') ?: null" />
        <x-ui.campo name="pin" wire:model="pin" :label="__('jota.acceso.pin_label')" :pin="true" :error="$errors->first('pin') ?: null" />

        <x-ui.boton type="submit" wire:loading.attr="disabled" wire:target="ingresar">
            <span wire:loading wire:target="ingresar"><x-heroicon-o-arrow-path class="size-5 animate-giro" aria-hidden="true" /></span>
            {{ __('jota.acceso.ingresar_boton') }}
        </x-ui.boton>
        <x-ui.boton variante="secundario" :href="route('activar')">{{ __('jota.acceso.primera_vez') }}</x-ui.boton>
        <x-ui.boton variante="enlace" :bloque="false" wire:click="$toggle('ayuda')" class="mx-auto" aria-expanded="{{ $ayuda ? 'true' : 'false' }}">{{ __('jota.acceso.olvido_pin') }}</x-ui.boton>
    </form>

    @if ($ayuda)
        <x-ui.aviso tono="info" :titulo="__('jota.acceso.ayuda_titulo')">
            <ol class="mt-1 list-decimal space-y-1 pl-5">
                <li>{{ __('jota.acceso.ayuda_paso_1') }}</li>
                <li>{{ __('jota.acceso.ayuda_paso_2') }}</li>
            </ol>
        </x-ui.aviso>
    @endif
</x-ui.pantalla>
