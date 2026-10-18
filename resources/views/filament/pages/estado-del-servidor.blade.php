<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-400">Lo que ve la <strong>web</strong> (no la consola). Si algo está en amarillo o en rojo, la ayuda dice cómo resolverlo.</p>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 text-gray-500 dark:border-white/10">
                <tr><th class="px-4 py-3 font-semibold">Revisión</th><th class="px-4 py-3 font-semibold">Valor</th><th class="px-4 py-3 font-semibold">Estado</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($this->revisiones() as $r)
                    <tr>
                        <td class="px-4 py-3 align-top">
                            <div class="font-medium">{{ $r['nombre'] }}</div>
                            @if ($r['estado'] !== 'ok')<div class="mt-1 text-xs text-gray-500">{{ $r['ayuda'] }}</div>@endif
                        </td>
                        <td class="px-4 py-3 align-top font-mono">{{ $r['valor'] }}</td>
                        <td class="px-4 py-3 align-top font-semibold">
                            @if ($r['estado'] === 'ok')<span class="text-green-700">✔ Bien</span>
                            @elseif ($r['estado'] === 'aviso')<span class="text-amber-600">⚠ Revisar</span>
                            @else<span class="text-red-600">✘ Problema</span>@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
