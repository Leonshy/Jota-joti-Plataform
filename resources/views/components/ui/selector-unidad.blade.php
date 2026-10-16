{{-- Solo para el administrador: elige sobre qué unidad actúan las pantallas de dirigente. Es un envío común (recarga la página). --}}
@if (auth()->user()?->esAdmin())
    <form method="post" action="{{ route('dirigente.unidad') }}" class="flex items-center gap-2 rounded-md bg-fondo-suave p-2">
        @csrf
        <label for="selector-unidad" class="shrink-0 pl-2 text-sm font-bold">{{ __('jota.dirigente.unidad') }}</label>
        <select id="selector-unidad" name="unidad" onchange="this.form.submit()"
            class="min-h-tactil w-full rounded-sm border-2 border-borde-campo bg-superficie px-3 text-base text-texto focus:border-primario">
            @foreach (app(\App\Domain\Acceso\UnidadActiva::class)->opciones() as $id => $nombre)
                <option value="{{ $id }}" @selected($id === unidad_activa())>{{ $nombre }}</option>
            @endforeach
        </select>
        <noscript><button class="min-h-tactil px-3 font-bold text-primario">{{ __('jota.comun.continuar') }}</button></noscript>
    </form>
@endif
