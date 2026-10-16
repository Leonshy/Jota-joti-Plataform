@props(['filas', 'titulo'])

{{-- Solo puesto y puntaje (CLAUDE.md §2.8). El estilo depende únicamente del puesto: nada marca "tu equipo". --}}
<div class="overflow-hidden rounded-md bg-superficie shadow-tarjeta">
    <table class="w-full text-left">
        <caption class="sr-only">{{ $titulo }}</caption>
        <thead>
            <tr class="border-b border-borde text-sm text-texto-suave">
                <th scope="col" class="px-4 py-3 font-bold">{{ __('jota.ranking.puesto') }}</th>
                <th scope="col" class="px-4 py-3 text-right font-bold">{{ __('jota.ranking.puntaje') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-borde">
            @foreach ($filas as [$puesto, $puntaje])
                <tr>
                    <th scope="row" class="px-4 py-3 font-bold">
                        <span @class([
                            'numeros grid size-10 place-items-center rounded-full text-base',
                            'bg-aviso text-gris-900' => $puesto === 1,
                            'bg-cian-300 text-gris-900' => $puesto === 2,
                            'bg-rosa-300 text-gris-900' => $puesto === 3,
                            'bg-gris-100 text-gris-900' => $puesto > 3,
                        ])>{{ $puesto }}</span>
                        <span class="sr-only">{{ __('jota.ranking.puesto_n', ['n' => $puesto]) }}</span>
                    </th>
                    <td class="numeros px-4 py-3 text-right text-xl font-bold text-titulo">{{ number_format($puntaje, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
