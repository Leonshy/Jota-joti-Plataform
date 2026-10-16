<x-filament-panels::page>
    {{-- Se refresca solo cada 30 s, igual que la caché del ranking. --}}
    <div wire:poll.30s style="display: grid; gap: 1.5rem;">
        @forelse ($this->rankings() as $unidad => $filas)
            <x-filament::section :heading="$unidad" collapsible>
                @if (empty($filas))
                    <p>Todavía no hay participantes en esta unidad.</p>
                @else
                    @php($porEquipo = isset($filas[0]['patrulla']))
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <caption class="sr-only">Ranking de {{ $unidad }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300);">Puesto</th>
                                    <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300);">{{ $porEquipo ? 'Patrulla' : 'Nombre' }}</th>
                                    @unless ($porEquipo)
                                        <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300);">CI</th>
                                    @endunless
                                    <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300);">Grupo</th>
                                    <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300); text-align: right;">Puntaje</th>
                                    @if ($porEquipo)
                                        <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300); text-align: right;">De la patrulla</th>
                                        <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300); text-align: right;">Individuales</th>
                                        <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300); text-align: right;">Integrantes</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($filas as $f)
                                    <tr>
                                        <td style="padding: .5rem; border-bottom: 1px solid var(--gray-200);"><strong>{{ $f['posicion'] }}°</strong></td>
                                        <td style="padding: .5rem; border-bottom: 1px solid var(--gray-200);">{{ $porEquipo ? $f['patrulla'] : $f['nombre'] }}</td>
                                        @unless ($porEquipo)
                                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-200);">{{ $f['ci'] }}</td>
                                        @endunless
                                        <td style="padding: .5rem; border-bottom: 1px solid var(--gray-200);">{{ $f['grupo'] }}</td>
                                        <td style="padding: .5rem; border-bottom: 1px solid var(--gray-200); text-align: right;"><strong>{{ number_format($f['puntaje'], 0, ',', '.') }}</strong></td>
                                        @if ($porEquipo)
                                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-200); text-align: right;">{{ number_format($f['puntos_patrulla'], 0, ',', '.') }}</td>
                                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-200); text-align: right;">{{ number_format($f['puntos_individuales'], 0, ',', '.') }}</td>
                                            <td style="padding: .5rem; border-bottom: 1px solid var(--gray-200); text-align: right;">{{ $f['integrantes'] }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        @empty
            <p>Todavía no hay unidades cargadas.</p>
        @endforelse
    </div>
</x-filament-panels::page>
