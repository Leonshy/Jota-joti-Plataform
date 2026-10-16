@php($estado ??= null)
<x-layouts.movil titulo="Devolver">
    <x-ui.pantalla :titulo="__('jota.tienda.devolver_titulo')">
        <x-slot:barra><x-ui.barra-tienda activa="devolver" /></x-slot:barra>

        @if (in_array($estado, ['form', 'tope'], true))
            <x-ui.tarjeta tono="suave">
                <p class="text-sm text-texto-suave">{{ __('jota.tienda.devolver_a') }}</p>
                <p class="text-lg font-bold">Ana Benítez</p>
                <p class="text-sm text-texto-suave">CI 1234567 · Águilas</p>
            </x-ui.tarjeta>
            <dl class="numeros divide-y divide-borde rounded-md bg-fondo-suave px-4">
                <div class="flex justify-between py-3"><dt class="text-texto-suave">{{ __('jota.tienda.gasto') }}</dt><dd class="font-bold">40</dd></div>
                <div class="flex justify-between py-3"><dt class="text-texto-suave">{{ __('jota.tienda.ya_devuelto') }}</dt><dd class="font-bold">10</dd></div>
                <div class="flex justify-between py-3"><dt class="text-texto-suave">{{ __('jota.tienda.tope') }}</dt><dd class="font-bold text-titulo">30</dd></div>
            </dl>
            <form action="#" method="post" class="flex flex-col gap-4">
                <x-ui.campo name="monto" :label="__('jota.tienda.monto_devolver')" type="number" inputmode="numeric"
                    :valor="$estado === 'tope' ? '50' : '20'" :error="$estado === 'tope' ? __('jota.tienda.error_tope', ['max' => cantidad(30, 'jli')]) : null" />
                <x-ui.campo name="motivo" :label="__('jota.tienda.motivo_label')" :area="true" :filas="3" valor="Producto en mal estado" />
                <x-ui.aviso tono="info">{{ __('jota.tienda.devolver_aviso') }}</x-ui.aviso>
                <x-ui.boton type="submit">{{ __('jota.tienda.devolver') }}</x-ui.boton>
            </form>
        @else
            <x-ui.campo name="busqueda" :label="__('jota.tienda.devolver_busqueda')" valor="Ana" :ayuda="__('jota.tienda.devolver_busqueda_ayuda')" />
            <x-ui.lista>
                <x-ui.fila :href="url('/diseno/t-devolver?estado=form')" icono="user" titulo="Ana Benítez" subtitulo="CI 1234567 · Águilas" />
                <x-ui.fila :href="url('/diseno/t-devolver?estado=form')" icono="user" titulo="Ana Gómez" subtitulo="CI 2345678 · Pumas" />
            </x-ui.lista>
        @endif
    </x-ui.pantalla>
</x-layouts.movil>
