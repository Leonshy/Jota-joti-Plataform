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

                @php($personas = $this->personasPorUnidad()[$unidad] ?? [])
                @if (! empty($personas))
                    <h3 style="margin-top: 1.5rem; font-weight: 600;">Personas con más puntos</h3>
                    <p style="font-size: .85rem; color: var(--gray-500);">Solo los puntos individuales: los de la patrulla no se suman acá.</p>
                    <div style="overflow-x: auto; max-width: 36rem;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <caption class="sr-only">Personas con más puntos de {{ $unidad }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300); text-align: right;">Puesto</th>
                                    <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300);">Participante</th>
                                    <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300); text-align: right;">Puntos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($personas as $p)
                                    <tr @if ($p['posicion'] <= 3) style="font-weight: 700;" @endif>
                                        <td style="padding: .4rem .5rem; border-bottom: 1px solid var(--gray-200); text-align: right;">{{ $p['posicion'] }}</td>
                                        <td style="padding: .4rem .5rem; border-bottom: 1px solid var(--gray-200);">{{ $p['nombre'] }}</td>
                                        <td style="padding: .4rem .5rem; border-bottom: 1px solid var(--gray-200); text-align: right;">{{ number_format($p['puntaje'], 0, ',', '.') }}</td>
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
