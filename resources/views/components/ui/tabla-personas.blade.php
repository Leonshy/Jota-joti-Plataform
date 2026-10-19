@props(['filas', 'titulo'])

{{-- Personas con más puntos de una unidad: Puesto · Participante · Puntos. Solo para el dirigente y el admin (nunca para el participante). --}}
<div class="overflow-hidden rounded-md bg-superficie shadow-tarjeta">
    <table class="w-full text-left">
        <caption class="sr-only">{{ $titulo }}</caption>
        <thead>
            <tr class="border-b border-borde text-sm text-texto-suave">
                <th scope="col" class="px-3 py-3 font-bold">{{ __('jota.ranking.puesto') }}</th>
                <th scope="col" class="px-2 py-3 font-bold">{{ __('jota.ranking.participante') }}</th>
                <th scope="col" class="px-3 py-3 text-right font-bold">{{ __('jota.ranking.puntos') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-borde">
            @foreach ($filas as $f)
                <tr @class(['font-bold' => $f['posicion'] <= 3])>
                    <th scope="row" class="numeros px-3 py-2.5 text-right font-[inherit]">{{ $f['posicion'] }}<span class="sr-only"> {{ __('jota.ranking.puesto') }}</span></th>
                    <td class="min-w-0 break-words px-2 py-2.5">{{ $f['nombre'] }}</td>
                    <td class="numeros px-3 py-2.5 text-right">{{ number_format($f['puntaje'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
