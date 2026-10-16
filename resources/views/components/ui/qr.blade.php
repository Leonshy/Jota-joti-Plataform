@props(['svg', 'ciclo' => 60, 'rotar' => false])

{{-- El QR va siempre negro sobre blanco con zona de silencio: es lo que mejor leen las cámaras viejas. --}}
<div {{ $attributes->merge(['class' => 'mx-auto w-full max-w-72']) }} @if ($rotar) data-qr-rota data-ciclo="{{ $ciclo }}" @endif>
    <div class="rounded-md bg-white p-4 shadow-elevada">
        <div class="aspect-square w-full [&>svg]:size-full" data-qr-contenido role="img" aria-label="{{ __('jota.escanear.qr_alt') }}">{!! $svg !!}</div>
    </div>
    <div class="mt-4 h-2 overflow-hidden rounded-full bg-primario-suave" aria-hidden="true">
        <div class="h-full origin-left rounded-full bg-primario" data-qr-barra @if ($rotar) style="animation: qr-cuenta {{ $ciclo }}s linear infinite" @endif></div>
    </div>
</div>
