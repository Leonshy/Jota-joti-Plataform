<x-ui.pantalla :titulo="__('jota.dirigente.penalizaciones_titulo')">
    <x-slot:barra><x-ui.barra-dirigente activa="penalizaciones" modo="app" /></x-slot:barra>

    <x-ui.selector-unidad />

    @if ($resultado)<x-ui.aviso tono="exito" :titulo="$resultado['titulo']">{{ $resultado['texto'] }}</x-ui.aviso>@endif

    <form wire:submit="aplicar" class="flex flex-col gap-4" novalidate>
        <div class="grid grid-cols-2 gap-2 rounded-full bg-primario-suave p-1" role="radiogroup" aria-label="{{ __('jota.dirigente.nueva_penalizacion') }}">
            @foreach (['persona' => 'penalizar_persona', 'patrulla' => 'penalizar_patrulla'] as $valor => $clave)
                <label class="grid min-h-tactil cursor-pointer place-items-center rounded-full px-3 text-center text-sm font-bold transition duration-150 has-[:checked]:bg-primario has-[:checked]:text-sobre-primario has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-azul-600">
                    <input type="radio" wire:model.live="tipo" value="{{ $valor }}" class="sr-only">
                    {{ __('jota.dirigente.'.$clave) }}
                </label>
            @endforeach
        </div>

        @if ($tipo === 'persona')
            @if ($persona)
                <x-ui.tarjeta class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-bold">{{ $persona['nombre'] }}</p>
                        @if ($persona['detalle'])<p class="text-sm text-texto-suave">{{ $persona['detalle'] }}</p>@endif
                    </div>
                    <x-ui.boton variante="enlace" :bloque="false" type="button" wire:click="cambiarPersona">{{ __('jota.dirigente.cambiar') }}</x-ui.boton>
                </x-ui.tarjeta>
            @else
                <x-ui.campo name="buscar" wire:model.live.debounce.300ms="buscar" :label="__('jota.dirigente.buscar_persona')" autocomplete="off" :error="$errors->first('buscar') ?: null" />
                @if ($resultados)
                    <x-ui.lista>
                        @foreach ($resultados as $r)
                            <li wire:key="r-{{ $r['id'] }}">
                                <button type="button" wire:click="elegir({{ $r['id'] }})" class="flex min-h-16 w-full flex-col justify-center px-4 py-2 text-left transition duration-150 hover:bg-fondo-suave active:bg-primario-suave">
                                    <span class="font-bold">{{ $r['nombre'] }}</span>
                                    @if ($r['detalle'])<span class="text-sm text-texto-suave">{{ $r['detalle'] }}</span>@endif
                                </button>
                            </li>
                        @endforeach
                    </x-ui.lista>
                @elseif (mb_strlen(trim($buscar)) >= 2)
                    <p class="text-sm text-texto-suave">{{ __('jota.dirigente.sin_resultados') }}</p>
                @endif
            @endif
        @else
            <div>
                <label for="campo-patrullaId" class="mb-1.5 block text-sm font-bold">{{ __('jota.dirigente.patrulla_label') }}</label>
                <select id="campo-patrullaId" wire:model="patrullaId" @if ($errors->has('patrullaId')) aria-invalid="true" @endif
                    class="block min-h-boton w-full rounded-sm border-2 border-borde-campo bg-superficie px-4 text-lg text-texto focus:border-primario">
                    <option value="">{{ __('jota.dirigente.elegir_patrulla') }}</option>
                    @foreach ($patrullas as $id => $nombre)<option value="{{ $id }}">{{ $nombre }}</option>@endforeach
                </select>
                @error('patrullaId')<p class="mt-1.5 text-xs font-bold text-error-texto" role="alert">{{ $message }}</p>@enderror
            </div>
        @endif

        <x-ui.campo name="cantidad" wire:model="cantidad" :label="__('jota.dirigente.cantidad_label')" type="text" inputmode="numeric" :error="$errors->first('cantidad') ?: null" />
        <x-ui.campo name="motivo" wire:model="motivo" :label="__('jota.dirigente.motivo_label')" :area="true" :filas="3" :error="$errors->first('motivo') ?: null" />
        <x-ui.aviso tono="info">{{ __('jota.dirigente.penalizar_aviso') }}</x-ui.aviso>
        <x-ui.boton variante="peligro" type="submit" wire:loading.attr="disabled" wire:target="aplicar">{{ __('jota.dirigente.aplicar') }}</x-ui.boton>
    </form>

    @if ($recientes)
        <x-ui.seccion :titulo="__('jota.dirigente.recientes')">
            <ul class="flex flex-col gap-3">
                @foreach ($recientes as $p)
                    <li wire:key="p-{{ $p['id'] }}">
                        <x-ui.tarjeta class="flex flex-col gap-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-bold">{{ $p['quien'] }} · −{{ cantidad($p['cantidad'], 'punto') }}</p>
                                    <p class="break-words text-sm text-texto-suave">«{{ $p['motivo'] }}» · {{ $p['cuando'] }}</p>
                                </div>
                                <x-ui.insignia :tono="$p['anulada'] ? 'neutro' : 'aviso'" :icono="! $p['anulada']">{{ $p['anulada'] ? __('jota.dirigente.anulada') : __('jota.dirigente.vigente') }}</x-ui.insignia>
                            </div>

                            @if (! $p['anulada'])
                                @if ($anulando === $p['id'])
                                    <form wire:submit="anular" class="flex flex-col gap-3 rounded-sm bg-fondo-suave p-3" novalidate>
                                        <p class="text-sm">{{ __('jota.dirigente.anular_aviso') }}</p>
                                        <x-ui.campo name="motivoAnulacion" wire:model="motivoAnulacion" :label="__('jota.dirigente.anular_motivo')" :area="true" :filas="2" :error="$errors->first('motivoAnulacion') ?: null" />
                                        <x-ui.boton type="submit" variante="peligro" wire:loading.attr="disabled" wire:target="anular">{{ __('jota.dirigente.anular') }}</x-ui.boton>
                                        <x-ui.boton variante="secundario" type="button" wire:click="noAnular">{{ __('jota.comun.cancelar') }}</x-ui.boton>
                                    </form>
                                @else
                                    <x-ui.boton variante="secundario" class="min-h-tactil! text-sm!" aria-label="{{ __('jota.dirigente.anular').': '.$p['quien'] }}" wire:click="pedirAnular({{ $p['id'] }})">{{ __('jota.dirigente.anular') }}</x-ui.boton>
                                @endif
                            @endif
                        </x-ui.tarjeta>
                    </li>
                @endforeach
            </ul>
        </x-ui.seccion>
    @endif
</x-ui.pantalla>
