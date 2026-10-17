<x-ui.pantalla wire:poll.30s :titulo="__('jota.misiones.titulo')">
    <x-slot:barra><x-ui.barra-participante activa="misiones" modo="app" /></x-slot:barra>

    @if ($misiones === [])
        <x-ui.vacio icono="flag" :titulo="__('jota.misiones.vacio_titulo')" :texto="__('jota.misiones.vacio_texto')" />
    @else
        <x-ui.lista>
            @foreach ($misiones as $m)
                @php($tono = ['nueva' => 'marca', 'pendiente' => 'aviso', 'aprobada' => 'exito', 'rechazada' => 'error', 'vencida' => 'neutro', 'cerrada' => 'neutro'][$m['estado']])
                <x-ui.fila :href="route('app.mision', $m['id'])" :icono="$m['por_patrulla'] ? 'user-group' : 'flag'" :titulo="$m['titulo']"
                    :subtitulo="($m['por_patrulla'] ? __('jota.misiones.patrulla') : __('jota.misiones.individual')).' · '.cantidad($m['puntos'], 'punto').(filled($m['lugar'] ?? null) ? ' · '.$m['lugar'] : '')">
                    <x-slot:abajo><x-ui.insignia :tono="$tono">{{ __('jota.misiones.estados.'.$m['estado']) }}</x-ui.insignia></x-slot:abajo>
                </x-ui.fila>
            @endforeach
        </x-ui.lista>
    @endif
</x-ui.pantalla>
