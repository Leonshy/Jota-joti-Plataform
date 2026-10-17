@props(['name', 'label'])

{{-- Casilla con el área táctil completa en la etiqueta. --}}
<label for="campo-{{ $name }}" class="flex min-h-tactil cursor-pointer items-center gap-3 rounded-sm px-1 has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-azul-600">
    <input id="campo-{{ $name }}" name="{{ $name }}" type="checkbox" {{ $attributes->merge(['class' => 'size-6 shrink-0 rounded-sm border-2 border-borde-campo accent-primario']) }}>
    <span class="font-bold">{{ $label }}</span>
</label>
