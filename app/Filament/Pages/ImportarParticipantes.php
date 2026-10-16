<?php

namespace App\Filament\Pages;

use App\Domain\Exportaciones\Csv;
use App\Domain\Participantes\ImportacionInvalida;
use App\Domain\Participantes\ImportarParticipantes as Importador;
use App\Models\Grupo;
use App\Models\Unidad;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Carga masiva de participantes (§4.2). Dos pasos: "Validar" revisa el archivo sin guardar nada y
 * muestra los errores línea por línea; "Importar" guarda todo, o nada si hay algún error.
 */
class ImportarParticipantes extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.pages.importar-participantes';

    protected static ?string $slug = 'importar-participantes';

    protected static ?string $title = 'Importar participantes';

    protected static ?string $navigationLabel = 'Importar participantes';

    protected static string|UnitEnum|null $navigationGroup = 'Estructura del evento';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null resumen del último análisis o importación */
    public ?array $resultado = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('archivo')
                    ->label('Archivo CSV')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'text/x-csv'])
                    ->maxSize(2048)
                    ->storeFiles(false) // se lee en memoria; el archivo nunca queda guardado
                    ->helperText('Hasta 2 MB y 5000 filas. Columnas: ci, nombre, grupo, unidad y, si querés, rol y patrulla.')
                    ->required(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('plantilla')
                ->label('Descargar plantilla')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->action(function () {
                    $grupo = Grupo::query()->orderBy('id')->value('nombre') ?? 'Nombre del grupo';
                    $equipo = Unidad::query()->where('modo_ranking', 'equipo')->orderBy('id')->value('nombre') ?? 'Tropa';
                    $individual = Unidad::query()->where('modo_ranking', 'individual')->orderBy('id')->value('nombre') ?? 'Rovers';

                    return Csv::descargar('plantilla-participantes.csv', ['ci', 'nombre', 'grupo', 'unidad', 'rol', 'patrulla'], [
                        ['1234567', 'Ana Benítez', $grupo, $equipo, 'beneficiario', 'Águilas'],
                        ['7654321', 'Luis Gómez', $grupo, $individual, 'beneficiario', ''],
                    ]);
                }),
        ];
    }

    public function validar(): void
    {
        $this->analizar(soloValidar: true);
    }

    public function importar(): void
    {
        $this->analizar(soloValidar: false);
    }

    private function analizar(bool $soloValidar): void
    {
        $estado = $this->form->getState();
        $archivo = is_array($estado['archivo']) ? reset($estado['archivo']) : $estado['archivo'];

        try {
            $r = app(Importador::class)(auth()->user(), $archivo->get(), $soloValidar);
        } catch (ImportacionInvalida $e) {
            $this->resultado = ['fatal' => $e->getMessage()];

            return;
        }

        $this->resultado = [
            'simulacro' => $r->simulacro,
            'valido' => $r->valido(),
            'filas' => $r->filas,
            'importados' => $r->importados,
            'errores' => $r->errores,
            'lineasConError' => $r->lineasConError(),
            'patrullasNuevas' => $r->patrullasNuevas,
        ];

        if (! $soloValidar && $r->valido()) {
            Notification::make()->title("Listo: se cargaron {$r->importados} participantes.")->success()->send();
            $this->form->fill();
        }
    }
}
