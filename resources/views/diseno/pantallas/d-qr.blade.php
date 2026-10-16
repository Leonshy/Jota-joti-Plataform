@php($estado ??= null)
<x-layouts.movil titulo="Asistencia · QR">
    {{-- Pantalla completa pensada para proyectarse y leerse a unos 3 metros: nada de barra inferior. --}}
    <main id="contenido" tabindex="-1" class="mx-auto flex min-h-dvh w-full max-w-md flex-col md:max-w-2xl items-center justify-center gap-6 bg-fondo px-5 py-8 text-center focus:outline-none">
        <h1 class="text-xl text-titulo">Estación JOTA: radioaficionados</h1>

        @if ($estado === 'sin-conexion')
            <x-ui.aviso tono="aviso" class="text-left">{{ __('jota.dirigente.qr_sin_conexion') }}</x-ui.aviso>
        @endif

        <x-ui.qr :svg="$qr" :rotar="true" :ciclo="60" class="max-w-80 md:max-w-lg" />

        <div>
            <p class="numeros text-4xl font-bold text-titulo" data-cuenta-regresiva="42" aria-live="off">0:42</p>
            <p class="text-sm text-texto-suave">{{ __('jota.dirigente.qr_cambia') }}</p>
        </div>

        <p class="max-w-xs text-lg">{{ __('jota.dirigente.qr_instruccion') }}</p>

        <x-ui.boton variante="secundario" :href="url('/diseno/d-asistencia')" icono="arrows-pointing-in">{{ __('jota.dirigente.qr_salir') }}</x-ui.boton>
    </main>
</x-layouts.movil>
