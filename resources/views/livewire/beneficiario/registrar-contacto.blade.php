{{-- Un único elemento raíz: Livewire necesita uno aunque la vista tenga dos ramas. --}}
<div>
@if ($enviada)
    <x-ui.pantalla :h1-propio="true" :completa="true">
        <x-ui.resultado tono="exito" :titulo="__('jota.contactos.enviada_titulo')" :texto="__('jota.contactos.enviada_texto')">
            <x-ui.boton type="button" wire:click="otro">{{ __('jota.contactos.cargar_otro') }}</x-ui.boton>
            <x-ui.boton variante="secundario" :href="route('app.contactos', $tipo)">{{ __('jota.contactos.volver_lista') }}</x-ui.boton>
        </x-ui.resultado>
    </x-ui.pantalla>
@else
    <x-ui.pantalla :volver="route('app.contactos', $tipo)" :titulo="__('jota.contactos.form_titulo', ['tipo' => __('jota.contactos.'.$tipo)])">
        <form wire:submit="enviar" class="flex flex-col gap-5" novalidate x-data="{ subiendo: false }">
            @if ($error)
                <x-ui.aviso tono="error" :titulo="__('jota.contactos.error_general')">{{ $error }}</x-ui.aviso>
            @endif

            @if ($esJota)
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.campo name="fecha" type="date" wire:model="fecha" :label="__('jota.contactos.fecha')" />
                    <x-ui.campo name="hora" type="time" wire:model="hora" :label="__('jota.contactos.hora')" />
                </div>
                <x-ui.campo name="estacion" wire:model="estacion" :label="__('jota.contactos.estacion')" :ayuda="__('jota.contactos.estacion_ayuda')"
                    autocapitalize="characters" autocomplete="off" spellcheck="false" placeholder="ZP5ZDM" />
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.campo name="rst_tx" wire:model="rst_tx" :label="__('jota.contactos.rst_tx')" inputmode="numeric" autocomplete="off" placeholder="5-9" />
                    <x-ui.campo name="rst_rx" wire:model="rst_rx" :label="__('jota.contactos.rst_rx')" inputmode="numeric" autocomplete="off" placeholder="5-9" />
                </div>
                <p class="-mt-3 text-xs text-texto-suave">{{ __('jota.contactos.rst_ayuda') }}</p>
                <x-ui.campo name="frecuencia" wire:model="frecuencia" :label="__('jota.contactos.frecuencia')" :ayuda="__('jota.contactos.frecuencia_ayuda')" inputmode="decimal" autocomplete="off" placeholder="7.125" />
                <x-ui.opciones name="emision" wire:model="emision" :label="__('jota.contactos.emision')" :opciones="$emisiones" :vacio="__('jota.contactos.emision_elegir')" />
                <x-ui.campo name="obs" wire:model="obs" :label="__('jota.contactos.obs')" :ayuda="__('jota.contactos.obs_ayuda')" maxlength="120" autocomplete="off" />
                <div class="flex flex-col">
                    <x-ui.casilla name="qsl_r" wire:model="qsl_r" :label="__('jota.contactos.qsl_r')" />
                    <x-ui.casilla name="qsl_e" wire:model="qsl_e" :label="__('jota.contactos.qsl_e')" />
                </div>
            @else
                <x-ui.campo name="grupo_scout" wire:model="grupo_scout" :label="__('jota.contactos.grupo_scout')" maxlength="120" autocomplete="off" />
                <x-ui.campo name="ciudad" wire:model="ciudad" :label="__('jota.contactos.ciudad')" maxlength="120" autocomplete="off" />
                <x-ui.campo name="unidad_scout" wire:model="unidad_scout" :label="__('jota.contactos.unidad_scout')" :ayuda="__('jota.contactos.unidad_scout_ayuda')" maxlength="120" autocomplete="off" />

                <fieldset class="flex flex-col gap-1">
                    <legend class="mb-1.5 text-sm font-bold">{{ __('jota.contactos.pais') }}</legend>
                    <label class="flex min-h-tactil cursor-pointer items-center gap-3">
                        <input type="radio" name="procedencia" value="paraguay" wire:model.live="procedencia" class="size-6 accent-primario">
                        <span class="font-bold">{{ __('jota.contactos.pais_paraguay') }}</span>
                    </label>
                    <label class="flex min-h-tactil cursor-pointer items-center gap-3">
                        <input type="radio" name="procedencia" value="otro" wire:model.live="procedencia" class="size-6 accent-primario">
                        <span class="font-bold">{{ __('jota.contactos.pais_otro') }}</span>
                    </label>
                </fieldset>
                @if ($procedencia === 'otro')
                    <x-ui.campo name="pais_otro" wire:model="pais_otro" :label="__('jota.contactos.pais_nombre')" maxlength="80" autocomplete="off" />
                @endif

                <div>
                    <p class="mb-2 text-sm font-bold">{{ __('jota.contactos.captura') }}</p>
                    @if ($captura)
                        <div class="relative">
                            <x-ui.foto :n="1" :src="$vista" />
                            <button type="button" wire:click="quitarCaptura" aria-label="{{ __('jota.contactos.captura_quitar') }}"
                                class="absolute right-1 top-1 grid size-tactil place-items-center rounded-full bg-fondo/95 text-error-texto shadow-tarjeta active:scale-95">
                                <x-heroicon-o-x-mark class="size-5" aria-hidden="true" />
                            </button>
                        </div>
                    @else
                        <label class="relative grid aspect-[4/3] cursor-pointer place-items-center rounded-sm border-2 border-dashed border-primario bg-fondo-suave text-primario transition duration-150 hover:bg-primario-suave active:bg-primario-suave has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-azul-600">
                            {{-- Se achica la captura en el celular antes de subirla (ver jotaFotoLiviana en app.js). --}}
                            <input type="file" accept="image/*" class="sr-only"
                                x-on:change="const f = $event.target.files[0]; $event.target.value = ''; if (f) { subiendo = true; jotaFotoLiviana(f).then((a) => $wire.upload('captura', a, () => subiendo = false, () => subiendo = false)).catch(() => subiendo = false) }">
                            <span class="flex flex-col items-center gap-1 text-sm font-bold" x-show="! subiendo">
                                <x-heroicon-o-camera class="size-7" aria-hidden="true" />
                                {{ __('jota.contactos.captura_agregar') }}
                            </span>
                            <span class="flex flex-col items-center gap-1 text-sm font-bold" x-show="subiendo" style="display: none">
                                <x-heroicon-o-arrow-path class="size-7 animate-giro" aria-hidden="true" />
                                {{ __('jota.comun.cargando') }}
                            </span>
                        </label>
                    @endif
                    @if ($errors->has('captura'))
                        <p class="mt-2 text-sm font-bold text-error-texto" role="alert">{{ \Illuminate\Support\Str::startsWith($errors->first('captura'), ['validation.', 'The ']) ? __('jota.contactos.error_subida') : $errors->first('captura') }}</p>
                    @endif
                    <p class="mt-2 text-xs text-texto-suave">{{ __('jota.contactos.captura_ayuda') }} {{ __('jota.contactos.privacidad') }}</p>
                </div>
            @endif

            <x-ui.boton type="submit" icono="paper-airplane" wire:loading.attr="disabled" wire:target="enviar" x-bind:disabled="subiendo">{{ __('jota.contactos.enviar') }}</x-ui.boton>
        </form>
    </x-ui.pantalla>
@endif
</div>
