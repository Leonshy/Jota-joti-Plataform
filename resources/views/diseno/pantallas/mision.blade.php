@php($estado ??= null)
@php($patrulla = $estado === 'patrulla')
<x-layouts.movil titulo="Misión">
    <x-ui.pantalla :volver="url('/diseno/misiones')" :titulo="__('jota.misiones.titulo')" :h1-propio="true">
        <div class="flex flex-col gap-3">
            <h1 class="text-2xl text-titulo">{{ $patrulla ? 'Nudos en equipo' : 'Bienvenida al JOTA-JOTI' }}</h1>
            <div class="flex flex-wrap gap-2">
                <x-ui.insignia tono="marca">{{ $patrulla ? __('jota.misiones.patrulla') : __('jota.misiones.individual') }}</x-ui.insignia>
                <x-ui.insignia tono="info">{{ cantidad($patrulla ? 50 : 10, 'punto') }}</x-ui.insignia>
                <x-ui.insignia tono="neutro">{{ $estado === 'vencida' ? __('jota.misiones.estados.vencida') : __('jota.misiones.vence', ['hora' => '18:00']) }}</x-ui.insignia>
            </div>
            <p>{{ $patrulla ? 'Armen entre toda la patrulla un nudo de ocho y subí la foto del equipo.' : 'Sacate una foto con tu uniforme y contanos de dónde venís.' }}</p>
        </div>

        @switch($estado)
            @case('pendiente')
                <x-ui.aviso tono="aviso">{{ __('jota.misiones.pendiente_aviso') }}</x-ui.aviso>
                <x-ui.tarjeta>
                    <p class="text-sm font-bold">{{ __('jota.misiones.tu_entrega') }}</p>
                    <p class="mt-1">Soy Ana, de Asunción.</p>
                    <div class="mt-3 grid grid-cols-2 gap-3"><x-ui.foto :n="1" /><x-ui.foto :n="2" /></div>
                </x-ui.tarjeta>
                @break
            @case('rechazada')
                <x-ui.aviso tono="error" :titulo="__('jota.misiones.rechazada_aviso')">«La foto está borrosa. Sacala de nuevo con más luz.»</x-ui.aviso>
                <x-ui.boton :href="url('/diseno/entregar')">{{ __('jota.misiones.entregar_de_nuevo') }}</x-ui.boton>
                @break
            @case('aprobada')
                <x-ui.aviso tono="exito">{{ copia('jota.misiones.aprobada_aviso', ['puntos' => cantidad(10, 'punto'), 'jlis' => cantidad(10, 'jli')]) }}</x-ui.aviso>
                @break
            @case('vencida')
                <x-ui.aviso tono="error">{{ __('jota.misiones.vencida_aviso') }}</x-ui.aviso>
                <x-ui.boton :deshabilitado="true">{{ __('jota.misiones.entregar') }}</x-ui.boton>
                @break
            @case('patrulla')
                <x-ui.aviso tono="aviso">{{ __('jota.misiones.patrulla_entrego', ['nombre' => 'Beto']) }}</x-ui.aviso>
                <x-ui.boton :deshabilitado="true">{{ __('jota.misiones.entregar_patrulla') }}</x-ui.boton>
                @break
            @default
                <x-ui.boton :href="url('/diseno/entregar')" icono-final="arrow-right">{{ __('jota.misiones.entregar') }}</x-ui.boton>
        @endswitch
    </x-ui.pantalla>
</x-layouts.movil>
