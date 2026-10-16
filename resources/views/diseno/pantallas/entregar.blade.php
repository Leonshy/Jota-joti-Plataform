@php($estado ??= null)
<x-layouts.movil titulo="Entregar">
    <x-ui.pantalla :volver="url('/diseno/mision')" :titulo="__('jota.misiones.entrega_titulo')">
        <p class="text-texto-suave">{{ __('jota.misiones.entrega_subtitulo') }}</p>

        <form action="{{ url('/diseno/mision?estado=pendiente') }}" method="get" class="flex flex-col gap-5">
            <input type="hidden" name="estado" value="pendiente">
            <x-ui.campo name="texto" :label="__('jota.misiones.texto_label')" :area="true"
                :valor="$estado === 'error' ? '' : 'Soy Ana, de Asunción. Vengo con mi patrulla.'"
                :ayuda="__('jota.misiones.texto_ayuda')"
                :error="$estado === 'error' ? __('jota.misiones.error_vacia') : null" />

            <div>
                <p class="mb-2 text-sm font-bold">{{ __('jota.misiones.fotos_label', ['n' => $estado === 'error' ? 0 : 2]) }}</p>
                <div class="grid grid-cols-2 gap-3">
                    @if ($estado !== 'error')
                        <x-ui.foto :n="1" />
                        <x-ui.foto :n="2" estado="procesando" />
                    @endif
                    <button type="button" class="grid aspect-[4/3] place-items-center rounded-sm border-2 border-dashed border-primario bg-fondo-suave text-primario transition duration-150 hover:bg-primario-suave active:bg-primario-suave">
                        <span class="flex flex-col items-center gap-1 text-sm font-bold">
                            <x-heroicon-o-camera class="size-7" aria-hidden="true" />
                            {{ __('jota.misiones.foto_agregar') }}
                        </span>
                    </button>
                </div>
                <p class="mt-2 text-xs text-texto-suave">{{ __('jota.misiones.fotos_ayuda') }}</p>
            </div>

            <x-ui.boton type="submit" icono="paper-airplane">{{ __('jota.misiones.enviar') }}</x-ui.boton>
        </form>
    </x-ui.pantalla>
</x-layouts.movil>
