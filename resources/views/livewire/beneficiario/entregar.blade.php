{{-- Un único elemento raíz: Livewire necesita uno aunque la vista tenga dos ramas. --}}
<div>
@if ($enviada)
    <x-ui.pantalla :h1-propio="true" :completa="true">
        <x-ui.resultado tono="exito" :titulo="__('jota.misiones.enviada_titulo')" :texto="__('jota.misiones.enviada_texto')">
            <x-ui.boton :href="route('app.mision', $mision)">{{ __('jota.misiones.ver_mision') }}</x-ui.boton>
            <x-ui.boton variante="secundario" :href="route('app.misiones')">{{ __('jota.misiones.volver_misiones') }}</x-ui.boton>
        </x-ui.resultado>
    </x-ui.pantalla>
@else
    <x-ui.pantalla :volver="route('app.mision', $mision)" :titulo="__('jota.misiones.entrega_titulo')">
        <p class="text-texto-suave">{{ $mision->titulo }} · {{ __('jota.misiones.entrega_subtitulo') }}</p>

        <form wire:submit="enviar" class="flex flex-col gap-5" novalidate x-data="{ subiendo: false }">
            <x-ui.campo name="texto" wire:model="texto" :label="__('jota.misiones.texto_label')" :area="true"
                :ayuda="$errors->has('texto') ? null : __('jota.misiones.texto_ayuda')" :error="$errors->first('texto') ?: null" />

            <div>
                <p class="mb-2 text-sm font-bold">{{ __('jota.misiones.fotos_label', ['n' => count($fotos)]) }}</p>
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($fotos as $i => $f)
                        <div class="relative" wire:key="foto-{{ $f->getFilename() }}">
                            @if ($vistas[$i])
                                <x-ui.foto :n="$i + 1" :src="$vistas[$i]" />
                            @else
                                <x-ui.foto :n="$i + 1" />
                            @endif
                            <button type="button" wire:click="quitar({{ $i }})" aria-label="{{ __('jota.misiones.foto_quitar', ['n' => $i + 1]) }}"
                                class="absolute right-1 top-1 grid size-tactil place-items-center rounded-full bg-fondo/95 text-error-texto shadow-tarjeta active:scale-95">
                                <x-heroicon-o-x-mark class="size-5" aria-hidden="true" />
                            </button>
                        </div>
                    @endforeach

                    @if (count($fotos) < 4)
                        <label class="relative grid aspect-[4/3] cursor-pointer place-items-center rounded-sm border-2 border-dashed border-primario bg-fondo-suave text-primario transition duration-150 hover:bg-primario-suave active:bg-primario-suave has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-azul-600">
                            {{-- Se achica la foto en el celular antes de subirla (ver jotaFotoLiviana en app.js). --}}
                            <input type="file" accept="image/*" class="sr-only"
                                x-on:change="const f = $event.target.files[0]; $event.target.value = ''; if (f) { subiendo = true; jotaFotoLiviana(f).then((a) => $wire.upload('nueva', a, () => subiendo = false, () => subiendo = false)).catch(() => subiendo = false) }">
                            <span class="flex flex-col items-center gap-1 text-sm font-bold" x-show="! subiendo">
                                <x-heroicon-o-camera class="size-7" aria-hidden="true" />
                                {{ __('jota.misiones.foto_agregar') }}
                            </span>
                            <span class="flex flex-col items-center gap-1 text-sm font-bold" x-show="subiendo" style="display: none">
                                <x-heroicon-o-arrow-path class="size-7 animate-giro" aria-hidden="true" />
                                {{ __('jota.comun.cargando') }}
                            </span>
                        </label>
                    @endif
                </div>
                @if ($errors->has('fotos') || $errors->has('nueva'))
                    <p class="mt-2 text-sm font-bold text-error-texto" role="alert">{{ $errors->first('fotos') ?: __('jota.misiones.error_subida') }}</p>
                @endif
                <p class="mt-2 text-xs text-texto-suave">{{ __('jota.misiones.fotos_ayuda') }}</p>
            </div>

            <x-ui.boton type="submit" icono="paper-airplane" wire:loading.attr="disabled" wire:target="enviar" x-bind:disabled="subiendo">{{ __('jota.misiones.enviar') }}</x-ui.boton>
        </form>
    </x-ui.pantalla>
@endif
</div>
