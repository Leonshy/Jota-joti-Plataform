<?php

namespace App\Filament\Resources\Unidades;

use App\Enums\ModoRanking;
use App\Filament\Resources\Unidades\Pages\ManageUnidades;
use App\Models\Unidad;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UnidadResource extends Resource
{
    protected static ?string $model = Unidad::class;

    protected static ?string $slug = 'unidades';

    protected static ?string $modelLabel = 'unidad';

    protected static ?string $pluralModelLabel = 'unidades';

    protected static string|UnitEnum|null $navigationGroup = 'Estructura del evento';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre de la unidad')->required()->maxLength(255)->unique(ignoreRecord: true),
            Select::make('modo_ranking')
                ->label('Cómo se rankea')
                ->options(ModoRanking::class)
                ->required()
                ->native(false)
                // Con participantes o patrullas cargados, cambiar el modo dejaría el ranking inconsistente.
                ->disabled(fn (?Unidad $record) => $record?->tieneParticipantes() ?? false)
                ->helperText('Tropa y Caminantes se rankean por equipo; Rovers, individual. No se puede cambiar una vez que hay participantes.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Unidad')->searchable()->sortable(),
                TextColumn::make('modo_ranking')->label('Ranking')->badge(),
                TextColumn::make('usuarios_count')->label('Personas')->counts('usuarios'),
                TextColumn::make('patrullas_count')->label('Patrullas')->counts('patrullas'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (Unidad $record) => $record->puedeBorrarse()),
            ])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return ['index' => ManageUnidades::route('/')];
    }
}
