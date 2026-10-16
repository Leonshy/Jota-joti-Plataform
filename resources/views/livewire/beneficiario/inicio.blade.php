{{-- El Inicio se refresca solo: los saldos cambian cuando le aprueban una misión o escanea. --}}
<x-ui.pantalla wire:poll.30s>
    <x-slot:barra><x-ui.barra-participante activa="inicio" modo="app" /></x-slot:barra>

    <div class="flex items-center justify-between gap-3">
        <x-ui.logo :ancho="140" />
        <form method="post" action="{{ route('salir') }}">
            @csrf
            <x-ui.boton type="submit" variante="enlace" :bloque="false" icono="arrow-right-start-on-rectangle">{{ __('jota.comun.cerrar_sesion') }}</x-ui.boton>
        </form>
    </div>

    @if ($bienvenida)
        <x-ui.aviso tono="exito">{{ __('jota.acceso.bienvenida', ['nombre' => $bienvenida]) }}</x-ui.aviso>
    @endif

    <h1 class="text-2xl">{{ __('jota.inicio.saludo', ['nombre' => $nombre]) }}</h1>

    <div class="grid grid-cols-2 gap-3">
        <x-ui.saldo tono="cian" :etiqueta="__('jota.inicio.mis_puntos')" :ayuda="__('jota.inicio.puntos_ayuda')" :valor="$resumen['puntos']" icono="star" />
        <x-ui.saldo tono="rosa" :etiqueta="__('jota.inicio.mis_jlis')" :ayuda="__('jota.inicio.jlis_ayuda')" :valor="$resumen['jlis']" icono="jota-moneda" />
    </div>

    @if ($resumen['patrulla'])
        <x-ui.tarjeta tono="marca" class="flex items-center justify-between gap-3">
            <span class="flex min-w-0 items-center gap-2">
                <x-heroicon-o-user-group class="size-6 shrink-0" aria-hidden="true" />
                <span class="truncate font-bold">{{ __('jota.inicio.tu_patrulla', ['nombre' => $resumen['patrulla']['nombre']]) }}</span>
            </span>
            <span class="numeros shrink-0 text-xl font-bold">{{ cantidad($resumen['patrulla']['puntos'], 'punto') }}</span>
        </x-ui.tarjeta>
    @endif

    <x-ui.seccion :titulo="__('jota.inicio.para_vos')">
        @if ($resumen['misiones_por_entregar'] === 0 && ! $resumen['proximo_turno'])
            <x-ui.vacio icono="sparkles" :titulo="__('jota.misiones.vacio_titulo')" :texto="__('jota.inicio.vacio')">
                <x-ui.boton variante="secundario" :href="route('app.actividades')">{{ __('jota.actividades.titulo') }}</x-ui.boton>
            </x-ui.vacio>
        @else
            <x-ui.lista>
                @if ($resumen['misiones_por_entregar'] > 0)
                    <x-ui.fila :href="route('app.misiones')" icono="flag"
                        :titulo="trans_choice('jota.inicio.misiones_para_entregar', $resumen['misiones_por_entregar'])" :subtitulo="__('jota.inicio.misiones_nuevas_ayuda')" />
                @endif
                @if ($resumen['proximo_turno'])
                    <x-ui.fila :href="route('app.actividades')" icono="calendar-days" :titulo="__('jota.inicio.proximo_turno')"
                        :subtitulo="$resumen['proximo_turno']['actividad'].(filled($resumen['proximo_turno']['lugar'] ?? null) ? ' · '.$resumen['proximo_turno']['lugar'] : '').' · '.($resumen['proximo_turno']['por_patrulla']
                            ? __('jota.inicio.turno_patrulla', ['cuando' => cuando($resumen['proximo_turno']['inicio'])])
                            : cuando($resumen['proximo_turno']['inicio']))" />
                @endif
                <x-ui.fila :href="route('app.historial')" icono="clock" :titulo="__('jota.inicio.historial')" />
            </x-ui.lista>
        @endif
    </x-ui.seccion>
</x-ui.pantalla>
