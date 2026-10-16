<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">1. Elegí el archivo</x-slot>
        <x-slot name="description">
            Cada participante se carga sin PIN: lo crea él mismo al ingresar con su CI. Si una patrulla no existe, se crea.
            Si hay un solo error, no se carga nada: corregí el archivo y volvé a probar.
        </x-slot>

        <form wire:submit.prevent="importar">
            {{ $this->form }}

            <div style="display: flex; gap: .75rem; margin-top: 1rem; flex-wrap: wrap;">
                <x-filament::button type="button" color="gray" wire:click="validar" wire:loading.attr="disabled">
                    Validar sin guardar
                </x-filament::button>
                <x-filament::button type="submit" wire:loading.attr="disabled">
                    Importar
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @if ($resultado)
        <x-filament::section>
            <x-slot name="heading">2. Resultado</x-slot>

            @if (isset($resultado['fatal']))
                <p role="alert"><strong>No se pudo leer el archivo.</strong> {{ $resultado['fatal'] }}</p>
            @else
                <p role="status">
                    @if ($resultado['valido'] && $resultado['simulacro'])
                        <strong>El archivo está bien.</strong> {{ $resultado['filas'] }} participantes listos para cargar. Todavía no se guardó nada: tocá «Importar».
                    @elseif ($resultado['valido'])
                        <strong>Se cargaron {{ $resultado['importados'] }} participantes.</strong>
                    @else
                        <strong>No se cargó nada.</strong>
                        Hay {{ count($resultado['errores']) }} {{ count($resultado['errores']) === 1 ? 'error' : 'errores' }}
                        en {{ $resultado['lineasConError'] }} de {{ $resultado['filas'] }} filas.
                    @endif
                </p>

                @if ($resultado['patrullasNuevas'])
                    <p>Patrullas que se crean: {{ implode(', ', $resultado['patrullasNuevas']) }}.</p>
                @endif

                @if ($resultado['errores'])
                    <div style="overflow-x: auto; margin-top: 1rem;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <caption class="sr-only">Errores del archivo, línea por línea</caption>
                            <thead>
                                <tr>
                                    <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300);">Línea</th>
                                    <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300);">Campo</th>
                                    <th scope="col" style="padding: .5rem; border-bottom: 1px solid var(--gray-300);">Qué pasó</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($resultado['errores'] as $error)
                                    <tr>
                                        <td style="padding: .5rem; border-bottom: 1px solid var(--gray-200);">{{ $error['linea'] }}</td>
                                        <td style="padding: .5rem; border-bottom: 1px solid var(--gray-200);">{{ $error['campo'] }}</td>
                                        <td style="padding: .5rem; border-bottom: 1px solid var(--gray-200);">{{ $error['mensaje'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
