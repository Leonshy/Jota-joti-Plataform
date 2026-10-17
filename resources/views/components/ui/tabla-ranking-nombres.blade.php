@props(['filas', 'titulo', 'modo'])

{{-- Ranking CON nombres: solo para el dirigente y el admin (nunca para el participante, que usa tabla-ranking). --}}
<div class="overflow-hidden rounded-md bg-superficie shadow-tarjeta">
    <table class="w-full text-left">
        <caption class="sr-only">{{ $titulo }}</caption>
        <thead>
            <tr class="border-b border-borde text-sm text-texto-suave">
                <th scope="col" class="px-3 py-3 font-bold">{{ __('jota.ranking.puesto') }}</th>
                <th scope="col" class="px-2 py-3 font-bold">{{ $modo === 'equipo' ? __('jota.ranking.equipo') : __('jota.ranking.persona') }}</th>
                <th scope="col" class="px-3 py-3 text-right font-bold">{{ __('jota.ranking.puntaje') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-borde">
            @foreach ($filas as $f)
                @php($puesto = $f['posicion'])
                <tr>
                    <th scope="row" class="px-3 py-3 font-bold">
                        <span @class([
                            'numeros grid size-10 place-items-center rounded-full text-base',
                            'bg-aviso text-gris-900' => $puesto === 1,
                            'bg-cian-300 text-gris-900' => $puesto === 2,
                            'bg-rosa-300 text-gris-900' => $puesto === 3,
                            'bg-gris-100 text-gris-900' => $puesto > 3,
                        ])>{{ $puesto }}</span>
                        <span class="sr-only">{{ __('jota.ranking.puesto_n', ['n' => $puesto]) }}</span>
                    </th>
                    <td class="min-w-0 px-2 py-3">
                        <span class="block break-words font-bold leading-snug">{{ $modo === 'equipo' ? $f['patrulla'] : $f['nombre'] }}</span>
                        <span class="block break-words text-sm text-texto-suave">
                            {{ $f['grupo'] }}@if ($modo === 'equipo') · {{ trans_choice('jota.ranking.integrantes', $f['integrantes'], ['count' => $f['integrantes']]) }}@endif
                        </span>
                        @if ($modo === 'equipo')
                            <span class="block text-xs text-texto-suave">{{ __('jota.ranking.desglose', ['patrulla' => number_format($f['puntos_patrulla'], 0, ',', '.'), 'individuales' => number_format($f['puntos_individuales'], 0, ',', '.')]) }}</span>
                        @endif
                    </td>
                    <td class="numeros px-3 py-3 text-right text-xl font-bold text-titulo">{{ number_format($f['puntaje'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
