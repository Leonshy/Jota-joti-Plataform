@props(['titulo', 'texto' => null, 'icono' => 'inbox'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 px-4 py-10 text-center']) }}>
    <span class="grid size-16 place-items-center rounded-full bg-primario-suave text-primario">
        <x-dynamic-component :component="icono($icono)" class="size-8" aria-hidden="true" />
    </span>
    <h2 class="text-lg">{{ $titulo }}</h2>
    @if ($texto)<p class="max-w-xs text-sm text-texto-suave">{{ $texto }}</p>@endif
    @if (! $slot->isEmpty())<div class="mt-2 w-full">{{ $slot }}</div>@endif
</div>
