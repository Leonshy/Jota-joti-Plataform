@php($estado ??= null)
<x-layouts.movil titulo="Penalizaciones">
    <x-ui.pantalla :titulo="__('jota.dirigente.penalizaciones_titulo')">
        <x-slot:barra><x-ui.barra-dirigente activa="penalizaciones" /></x-slot:barra>

        <form action="#" method="post" class="flex flex-col gap-4">
            <x-ui.campo name="persona" :label="__('jota.dirigente.buscar_persona')" valor="Ana Benítez" />
            <x-ui.campo name="cantidad" :label="__('jota.dirigente.cantidad_label')" type="number" inputmode="numeric"
                :valor="$estado === 'error' ? '0' : '5'" :error="$estado === 'error' ? __('jota.dirigente.error_cantidad') : null" />
            <x-ui.campo name="motivo" :label="__('jota.dirigente.motivo_label')" :area="true" :filas="3"
                :valor="$estado === 'error' ? '' : 'Llegó tarde a formar'" :error="$estado === 'error' ? __('jota.dirigente.error_motivo') : null" />
            <x-ui.aviso tono="info">{{ __('jota.dirigente.penalizar_aviso') }}</x-ui.aviso>
            <x-ui.boton variante="peligro" type="submit">{{ __('jota.dirigente.aplicar') }}</x-ui.boton>
        </form>

        <x-ui.seccion :titulo="__('jota.dirigente.recientes')">
            <x-ui.lista>
                <x-ui.fila icono="exclamation-triangle" titulo="Ana Benítez · −5 puntos" subtitulo="«Llegó tarde» · hoy 11:20">
                    <x-slot:derecha><x-ui.insignia tono="aviso">{{ __('jota.dirigente.vigente') }}</x-ui.insignia></x-slot:derecha>
                </x-ui.fila>
                <x-ui.fila icono="exclamation-triangle" titulo="Beto Cardozo · −10 puntos" subtitulo="«Error de dedo»">
                    <x-slot:derecha><x-ui.insignia tono="neutro" :icono="false">{{ __('jota.dirigente.anulada') }}</x-ui.insignia></x-slot:derecha>
                </x-ui.fila>
            </x-ui.lista>
        </x-ui.seccion>
    </x-ui.pantalla>
</x-layouts.movil>
