<div data-teclado class="grid grid-cols-3 gap-3" role="group" aria-label="{{ __('jota.tienda.teclado') }}">
    @foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9'] as $n)
        <button type="button" data-tecla="{{ $n }}" class="numeros min-h-16 rounded-md bg-superficie text-2xl font-bold text-texto shadow-tarjeta transition duration-150 active:scale-[0.97] active:bg-primario-suave">{{ $n }}</button>
    @endforeach
    <button type="button" data-tecla="limpiar" aria-label="{{ __('jota.tienda.borrar_todo') }}" class="min-h-16 rounded-md bg-fondo-suave text-lg font-bold text-primario transition duration-150 active:scale-[0.97] active:bg-primario-suave">C</button>
    <button type="button" data-tecla="0" class="numeros min-h-16 rounded-md bg-superficie text-2xl font-bold text-texto shadow-tarjeta transition duration-150 active:scale-[0.97] active:bg-primario-suave">0</button>
    <button type="button" data-tecla="borrar" aria-label="{{ __('jota.tienda.borrar_ultimo') }}" class="grid min-h-16 place-items-center rounded-md bg-fondo-suave text-primario transition duration-150 active:scale-[0.97] active:bg-primario-suave">
        <x-heroicon-o-backspace class="size-7" aria-hidden="true" />
    </button>
</div>
