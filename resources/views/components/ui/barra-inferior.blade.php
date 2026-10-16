@props(['items', 'activa' => null])

<nav aria-label="{{ __('jota.navegacion.principal') }}" class="fixed inset-x-0 bottom-0 z-20 mx-auto w-full max-w-md border-t border-borde bg-superficie pb-[env(safe-area-inset-bottom)]">
    <ul class="flex items-end justify-around px-1 pt-1.5">
        @foreach ($items as $it)
            @php($activo = $activa === $it['clave'])
            <li @class(['min-w-0', 'flex-[1.3]' => $it['centro'] ?? false, 'flex-1' => ! ($it['centro'] ?? false)])>
                <a href="{{ $it['href'] }}" @if ($activo) aria-current="page" @endif
                    @class([
                        'flex min-h-14 flex-col items-center justify-end gap-0.5 rounded-md px-0 pb-1.5 text-xs font-bold leading-tight no-underline',
                        'text-primario' => $activo,
                        'text-texto-suave' => ! $activo,
                    ])>
                    @if ($it['centro'] ?? false)
                        <span class="-mt-7 grid size-14 place-items-center rounded-full bg-primario text-sobre-primario shadow-elevada ring-4 ring-superficie">
                            <x-dynamic-component :component="icono($it['icono'])" class="size-7" aria-hidden="true" />
                        </span>
                    @else
                        <span @class(['rounded-full px-4 py-1', 'bg-primario-suave' => $activo])>
                            <x-dynamic-component :component="icono($it['icono'])" class="size-6" aria-hidden="true" />
                        </span>
                    @endif
                    <span class="max-w-full truncate">{{ $it['etiqueta'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
