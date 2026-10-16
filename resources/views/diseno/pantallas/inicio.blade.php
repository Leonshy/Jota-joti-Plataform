@php($estado ??= null)
<x-layouts.movil titulo="Inicio">
    <x-ui.pantalla>
        <x-slot:barra><x-ui.barra-participante activa="inicio" /></x-slot:barra>

        <div class="flex items-center justify-between gap-3">
            <x-ui.logo :ancho="140" />
            <x-ui.boton variante="enlace" :bloque="false" :href="url('/diseno/ingresar')" icono="arrow-right-start-on-rectangle">{{ __('jota.comun.cerrar_sesion') }}</x-ui.boton>
        </div>

        @if ($estado === 'bienvenida')
            <x-ui.aviso tono="exito">{{ __('jota.acceso.bienvenida', ['nombre' => 'Ana']) }}</x-ui.aviso>
        @endif

        <h1 class="text-2xl">{{ __('jota.inicio.saludo', ['nombre' => 'Ana']) }}</h1>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.saldo tono="cian" :etiqueta="__('jota.inicio.mis_puntos')" :ayuda="__('jota.inicio.puntos_ayuda')" :valor="10" icono="star" />
            <x-ui.saldo tono="rosa" :etiqueta="__('jota.inicio.mis_jlis')" :ayuda="__('jota.inicio.jlis_ayuda')" :valor="70" icono="jota-moneda" />
        </div>

        <x-ui.tarjeta tono="marca" class="flex items-center justify-between gap-3">
            <span class="flex min-w-0 items-center gap-2">
                <x-heroicon-o-user-group class="size-6 shrink-0" aria-hidden="true" />
                <span class="truncate font-bold">{{ __('jota.inicio.tu_patrulla', ['nombre' => 'Águilas']) }}</span>
            </span>
            <span class="numeros shrink-0 text-xl font-bold">{{ cantidad(100, 'punto') }}</span>
        </x-ui.tarjeta>

        <x-ui.seccion :titulo="__('jota.inicio.para_vos')">
            @if ($estado === 'vacio')
                <x-ui.vacio icono="sparkles" :titulo="__('jota.misiones.vacio_titulo')" :texto="__('jota.inicio.vacio')">
                    <x-ui.boton variante="secundario" :href="url('/diseno/actividades')">{{ __('jota.actividades.titulo') }}</x-ui.boton>
                </x-ui.vacio>
            @else
                <x-ui.lista>
                    <x-ui.fila :href="url('/diseno/misiones')" icono="flag" :titulo="trans_choice('jota.inicio.misiones_nuevas', 2)" :subtitulo="__('jota.inicio.misiones_nuevas_ayuda')" />
                    <x-ui.fila :href="url('/diseno/actividades')" icono="calendar-days" :titulo="__('jota.inicio.proximo_turno')" subtitulo="Taller de nudos · hoy 15:00" />
                    <x-ui.fila :href="url('/diseno/historial')" icono="clock" :titulo="__('jota.inicio.historial')" />
                </x-ui.lista>
            @endif
        </x-ui.seccion>
    </x-ui.pantalla>
</x-layouts.movil>
