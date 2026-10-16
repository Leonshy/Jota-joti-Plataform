<x-ui.pantalla :titulo="__('jota.tienda.devolver_titulo')">
    <x-slot:barra><x-ui.barra-tienda activa="devolver" modo="app" /></x-slot:barra>

    @if ($resultado)
        <x-ui.aviso tono="exito" :titulo="__('jota.tienda.devuelto_titulo')">{{ copia('jota.tienda.devuelto_texto', ['nombre' => $resultado['nombre'], 'monto' => $resultado['monto'], 'saldo' => $resultado['saldo']]) }}</x-ui.aviso>
    @endif

    @if ($persona)
        <x-ui.tarjeta class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm text-texto-suave">{{ __('jota.tienda.devolver_a') }}</p>
                <p class="truncate text-lg font-bold">{{ $persona['nombre'] }}</p>
                <p class="text-sm text-texto-suave">{{ $persona['detalle'] }}</p>
            </div>
            <x-ui.boton variante="enlace" :bloque="false" type="button" wire:click="cambiar">{{ __('jota.dirigente.cambiar') }}</x-ui.boton>
        </x-ui.tarjeta>

        <dl class="numeros divide-y divide-borde rounded-md bg-fondo-suave px-4">
            <div class="flex justify-between py-3"><dt class="text-texto-suave">{{ __('jota.tienda.gasto') }}</dt><dd class="font-bold">{{ number_format($compras['gastado'], 0, ',', '.') }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-texto-suave">{{ __('jota.tienda.ya_devuelto') }}</dt><dd class="font-bold">{{ number_format($compras['devuelto'], 0, ',', '.') }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-texto-suave">{{ __('jota.tienda.tope') }}</dt><dd class="font-bold text-titulo">{{ number_format($compras['maximo'], 0, ',', '.') }}</dd></div>
        </dl>

        @if ($compras['maximo'] === 0)
            <x-ui.aviso tono="aviso" :titulo="__('jota.tienda.nada_titulo')">{{ __('jota.tienda.nada_texto') }}</x-ui.aviso>
        @else
            <form wire:submit="devolver" class="flex flex-col gap-4" novalidate>
                <x-ui.campo name="monto" wire:model="monto" :label="__('jota.tienda.monto_devolver')" type="text" inputmode="numeric" :error="$errors->first('monto') ?: null" />
                <x-ui.campo name="motivo" wire:model="motivo" :label="__('jota.tienda.motivo_label')" :area="true" :filas="3" :error="$errors->first('motivo') ?: null" />
                <x-ui.aviso tono="info">{{ __('jota.tienda.devolver_aviso') }}</x-ui.aviso>
                <x-ui.boton type="submit" wire:loading.attr="disabled" wire:target="devolver">{{ __('jota.tienda.devolver') }}</x-ui.boton>
            </form>
        @endif
    @else
        <x-ui.campo name="buscar" wire:model.live.debounce.300ms="buscar" :label="__('jota.tienda.devolver_busqueda')" :ayuda="__('jota.tienda.devolver_busqueda_ayuda')" autocomplete="off" />
        @if ($resultados)
            <x-ui.lista>
                @foreach ($resultados as $r)
                    <li wire:key="r-{{ $r['id'] }}">
                        <button type="button" wire:click="elegir({{ $r['id'] }})" class="flex min-h-16 w-full flex-col justify-center px-4 py-2 text-left transition duration-150 hover:bg-fondo-suave active:bg-primario-suave">
                            <span class="font-bold">{{ $r['nombre'] }}</span>
                            <span class="text-sm text-texto-suave">{{ $r['detalle'] }}</span>
                        </button>
                    </li>
                @endforeach
            </x-ui.lista>
        @elseif (trim($buscar) !== '')
            <p class="text-sm text-texto-suave">{{ __('jota.tienda.devolver_sin_resultados') }}</p>
        @endif
    @endif
</x-ui.pantalla>
