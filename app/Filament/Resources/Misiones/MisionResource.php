<?php

namespace App\Filament\Resources\Misiones;

use App\Domain\Misiones\CerrarMision;
use App\Enums\Modalidad;
use App\Filament\Resources\Misiones\Pages\CreateMision;
use App\Filament\Resources\Misiones\Pages\EditMision;
use App\Filament\Resources\Misiones\Pages\ListMisiones;
use App\Models\Mision;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MisionResource extends Resource
{
    protected static ?string $model = Mision::class;

    protected static ?string $slug = 'misiones';

    protected static ?string $modelLabel = 'misión';

    protected static ?string $pluralModelLabel = 'misiones';

    protected static string|UnitEnum|null $navigationGroup = 'Juego';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $recordTitleAttribute = 'titulo';

    public static function form(Schema $schema): Schema
    {
        // Con entregas, cambiar la modalidad o el puntaje dejaría inconsistente lo ya acreditado.
        $conEntregas = fn (?Mision $record) => $record?->tieneEntregas() ?? false;

        return $schema->components([
            Section::make('La misión')->schema([
                TextInput::make('titulo')->label('Título')->required()->maxLength(255),
                Textarea::make('descripcion')->label('Qué tienen que hacer')->required()->rows(4)->maxLength(5000),
            ]),
            Section::make('Cómo se juega')->columns(2)->schema([
                Select::make('modalidad')->label('Modalidad')->options(Modalidad::class)->required()->native(false)
                    ->default(Modalidad::Individual)->disabled($conEntregas),
                TextInput::make('puntos')->label('Puntaje fijo')->numeric()->integer()->required()->minValue(1)->maxValue(100000)
                    ->disabled($conEntregas)
                    ->helperText('Se otorga completo al aprobar. En misiones por patrulla, cada integrante recibe en LisCoins el puntaje dividido entre los integrantes, redondeado hacia arriba.'),
                Select::make('unidades')->label('Unidades destino')->relationship('unidades', 'nombre')->multiple()->preload()->required()->native(false)
                    ->columnSpanFull(),
            ]),
            Section::make('Cuándo')->columns(2)->schema([
                DateTimePicker::make('lanza_at')->label('Se lanza')->required()->seconds(false)
                    ->helperText('Antes de esta hora la misión no se ve. Hora de Asunción.'),
                DateTimePicker::make('vence_at')->label('Vence (opcional)')->seconds(false)->after('lanza_at')
                    ->helperText('Sin vencimiento, queda abierta hasta que la cierres.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('unidades:id,nombre')
                ->withCount(['entregas', 'entregas as pendientes_count' => fn (Builder $q) => $q->pendientes()]))
            ->columns([
                TextColumn::make('titulo')->label('Misión')->searchable()->sortable()->wrap(),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->state(fn (Mision $record) => $record->estado())
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'abierta' => 'success',
                        'programada' => 'info',
                        'vencida' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('modalidad')->label('Modalidad')->badge(),
                TextColumn::make('puntos')->label('Puntos')->numeric()->sortable(),
                TextColumn::make('unidades.nombre')->label('Unidades')->badge(),
                TextColumn::make('lanza_at')->label('Se lanza')->dateTime('d/m H:i')->sortable(),
                TextColumn::make('vence_at')->label('Vence')->dateTime('d/m H:i')->placeholder('Sin vencimiento')->sortable(),
                TextColumn::make('pendientes_count')->label('Por revisar')->numeric()->sortable(),
                TextColumn::make('entregas_count')->label('Entregas')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('modalidad')->label('Modalidad')->options(Modalidad::class),
                SelectFilter::make('unidad')->label('Unidad')
                    ->relationship('unidades', 'nombre'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('cerrar')
                    ->label('Cerrar')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('warning')
                    ->visible(fn (Mision $record) => $record->cerrada_at === null && $record->estado() !== 'vencida')
                    ->requiresConfirmation()
                    ->modalHeading('Cerrar la misión')
                    ->modalDescription('Deja de aceptar entregas. Las que ya están pendientes se pueden seguir revisando.')
                    ->action(function (Mision $record) {
                        app(CerrarMision::class)(auth()->user(), $record);

                        Notification::make()->title('Misión cerrada.')->success()->send();
                    }),
                DeleteAction::make()->visible(fn (Mision $record) => ! $record->tieneEntregas()),
            ])
            ->defaultSort('lanza_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMisiones::route('/'),
            'create' => CreateMision::route('/crear'),
            'edit' => EditMision::route('/{record}/editar'),
        ];
    }
}
