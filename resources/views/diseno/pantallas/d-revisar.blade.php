@php($estado ??= null)
@php($patrulla = $estado === 'patrulla')
<x-layouts.movil titulo="Revisar entrega">
    <x-ui.pantalla :volver="url('/diseno/d-cola')" :titulo="__('jota.dirigente.revisar_titulo')" :h1-propio="true">
        @if ($estado === 'aprobada')
            <x-ui.resultado tono="exito" :titulo="__('jota.dirigente.aprobada_titulo')" :texto="copia('jota.dirigente.acredita_individual', ['puntos' => cantidad(10, 'punto'), 'jlis' => cantidad(10, 'jli'), 'nombre' => 'Ana Benítez'])">
                <x-ui.boton :href="url('/diseno/d-revisar')">{{ __('jota.dirigente.siguiente') }}</x-ui.boton>
                <x-ui.boton variante="secundario" :href="url('/diseno/d-cola')">{{ __('jota.dirigente.volver_cola') }}</x-ui.boton>
            </x-ui.resultado>
        @elseif ($estado === 'ya-revisada')
            <x-ui.resultado tono="aviso" :animar="false" :titulo="__('jota.dirigente.ya_revisada_titulo')" :texto="__('jota.dirigente.ya_revisada_texto')">
                <x-ui.boton :href="url('/diseno/d-cola')">{{ __('jota.dirigente.volver_cola') }}</x-ui.boton>
            </x-ui.resultado>
        @elseif (in_array($estado, ['rechazar', 'rechazar-error'], true))
            <h1 class="text-xl text-titulo">Rechazar la entrega de Ana</h1>
            <form action="{{ url('/diseno/d-revisar?estado=rechazada') }}" method="get" class="flex flex-col gap-4">
                <input type="hidden" name="estado" value="rechazada">
                <x-ui.campo name="motivo" :label="__('jota.dirigente.motivo_label')" :area="true" :ayuda="__('jota.dirigente.motivo_ayuda', ['nombre' => 'Ana'])"
                    :error="$estado === 'rechazar-error' ? __('jota.dirigente.error_motivo') : null" />
                <x-ui.boton variante="peligro" type="submit">{{ __('jota.dirigente.rechazar') }}</x-ui.boton>
                <x-ui.boton variante="secundario" :href="url('/diseno/d-revisar')">{{ __('jota.comun.cancelar') }}</x-ui.boton>
            </form>
        @elseif ($estado === 'rechazada')
            <x-ui.resultado tono="exito" :titulo="__('jota.dirigente.rechazada_titulo')" :texto="__('jota.dirigente.rechazada_texto', ['nombre' => 'Ana'])">
                <x-ui.boton :href="url('/diseno/d-cola')">{{ __('jota.dirigente.volver_cola') }}</x-ui.boton>
            </x-ui.resultado>
        @else
            <div>
                <h1 class="text-2xl text-titulo">{{ $patrulla ? 'Patrulla Zorros' : 'Ana Benítez' }}</h1>
                <p class="text-texto-suave">{{ $patrulla ? 'La entregó Carla Duarte · hace 3 min' : 'Patrulla Águilas · hace 12 min' }}</p>
            </div>

            <x-ui.tarjeta>
                <p class="text-sm text-texto-suave">{{ __('jota.dirigente.mision') }}</p>
                <p class="font-bold">{{ $patrulla ? 'Nudos en equipo' : 'Bienvenida al JOTA-JOTI' }}</p>
                <p class="mt-1 text-sm">{{ $patrulla ? copia('jota.dirigente.acredita_patrulla', ['puntos' => cantidad(50, 'punto'), 'jlis' => cantidad(13, 'jli')]) : copia('jota.dirigente.acredita_individual', ['puntos' => cantidad(10, 'punto'), 'jlis' => cantidad(10, 'jli'), 'nombre' => 'Ana']) }}</p>
            </x-ui.tarjeta>

            <x-ui.tarjeta>
                <p class="text-sm text-texto-suave">{{ __('jota.dirigente.texto') }}</p>
                <p class="mt-1">{{ $patrulla ? 'Lo armamos entre las cuatro, salió a la primera.' : 'Soy Ana, de Asunción. Vengo con mi patrulla.' }}</p>
            </x-ui.tarjeta>

            <x-ui.seccion :titulo="__('jota.dirigente.fotos')">
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.foto :n="1" /><x-ui.foto :n="2" />
                </div>
            </x-ui.seccion>

            <div class="sticky bottom-3 grid grid-cols-2 gap-3 rounded-lg bg-fondo/95 p-2 shadow-elevada">
                <x-ui.boton variante="secundario" :href="url('/diseno/d-revisar?estado=rechazar')">{{ __('jota.dirigente.rechazar') }}</x-ui.boton>
                <x-ui.boton :href="url('/diseno/d-revisar?estado=aprobada')" icono="check">{{ __('jota.dirigente.aprobar') }}</x-ui.boton>
            </div>
        @endif
    </x-ui.pantalla>
</x-layouts.movil>
