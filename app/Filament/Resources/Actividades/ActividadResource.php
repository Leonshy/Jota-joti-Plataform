<?php

namespace App\Filament\Resources\Actividades;

use App\Enums\Modalidad;
use App\Enums\TipoActividad;
use App\Filament\Resources\Actividades\Pages\CreateActividad;
use App\Filament\Resources\Actividades\Pages\EditActividad;
use App\Filament\Resources\Actividades\Pages\ListActividades;
use App\Filament\Resources\Actividades\RelationManagers\TurnosRelationManager;
use App\Models\Actividad;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ActividadResource extends Resource
{
    protected static ?string $model = Actividad::class;

    protected static ?string $slug = 'actividades';

    protected static ?string $modelLabel = 'actividad';

    protected static ?string $pluralModelLabel = 'talleres y estaciones';

    protected static ?string $navigationLabel = 'Talleres y estaciones';

    protected static string|UnitEnum|null $navigationGroup = 'Juego';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'titulo';

    public static function form(Schema $schema): Schema
    {
        // Con asistencias o cumplimientos, cambiar la modalidad o el puntaje dejaría datos inconsistentes.
        $conRegistros = fn (?Actividad $record) => $record?->tieneRegistros() ?? false;
        $esEstacion = fn ($tipo) => $tipo === TipoActividad::EstacionJota || $tipo === TipoActividad::EstacionJota->value;

        return $schema->components([
            Section::make('La actividad')->columns(2)->schema([
                TextInput::make('titulo')->label('Título')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('lugar')->label('Lugar')->required()->maxLength(120)->columnSpanFull()
                    ->helperText('Dónde se hace: lo ven los participantes en la agenda y en sus reservas. Ej.: «Cancha de fútbol», «Salón grande».'),
                Select::make('tipo')->label('Tipo')->options(TipoActividad::class)->required()->native(false)->default(TipoActividad::Taller)
                    ->live()->disabled($conRegistros)
                    // Al elegir «Estación JOTA» la modalidad pasa sola a «por equipo».
                    ->afterStateUpdated(fn ($state, Set $set) => $esEstacion($state) ? $set('modalidad', Modalidad::Patrulla->value) : null)
                    ->helperText('Taller: individual o por equipo. Estación JOTA: siempre por equipo, y cada horario es de un solo equipo.'),
                Select::make('modalidad')->label('Modalidad')->options(Modalidad::class)->required()->native(false)
                    ->default(Modalidad::Individual)
                    // La estación JOTA es siempre por equipo (el modelo también lo fuerza).
                    ->disabled(fn (?Actividad $record, Get $get) => $conRegistros($record) || $esEstacion($get('tipo')))
                    ->dehydrated(fn (?Actividad $record) => ! $conRegistros($record))
                    ->helperText('Individual: asisten con el QR y suman al instante. Por equipo: reserva la patrulla (los Rovers, toda la unidad) y el dirigente marca si cumplió.'),
                TextInput::make('puntos')->label('Puntaje')->numeric()->integer()->required()->minValue(0)->maxValue(100000)->default(0)
                    ->disabled($conRegistros)
                    ->helperText('Puede ser 0 (por ejemplo, una estación que no da puntos).'),
                Select::make('unidades')->label('Unidades destino')->relationship('unidades', 'nombre')->multiple()->preload()->required()->native(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('unidades:id,nombre')->withCount('turnos'))
            ->columns([
                TextColumn::make('titulo')->label('Actividad')->searchable()->sortable()->wrap(),
                TextColumn::make('lugar')->label('Lugar')->searchable()->placeholder('Sin lugar')->wrap(),
                TextColumn::make('tipo')->label('Tipo')->badge(),
                TextColumn::make('modalidad')->label('Modalidad')->badge(),
                TextColumn::make('puntos')->label('Puntos')->numeric()->sortable(),
                TextColumn::make('unidades.nombre')->label('Unidades')->badge(),
                TextColumn::make('turnos_count')->label('Turnos')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo')->label('Tipo')->options(TipoActividad::class),
                SelectFilter::make('modalidad')->label('Modalidad')->options(Modalidad::class),
                SelectFilter::make('unidad')->label('Unidad')->relationship('unidades', 'nombre'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (Actividad $record) => $record->puedeBorrarse()),
            ])
            ->defaultSort('titulo');
    }

    public static function getRelations(): array
    {
        return [TurnosRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActividades::route('/'),
            'create' => CreateActividad::route('/crear'),
            'edit' => EditActividad::route('/{record}/editar'),
        ];
    }
}
