<?php

namespace App\Filament\Resources\Grupos;

use App\Filament\Resources\Grupos\Pages\ManageGrupos;
use App\Models\Grupo;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class GrupoResource extends Resource
{
    protected static ?string $model = Grupo::class;

    protected static ?string $modelLabel = 'grupo';

    protected static ?string $pluralModelLabel = 'grupos';

    protected static string|UnitEnum|null $navigationGroup = 'Estructura del evento';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre del grupo')->required()->maxLength(255)->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Grupo')->searchable()->sortable(),
                TextColumn::make('usuarios_count')->label('Personas')->counts('usuarios'),
                TextColumn::make('patrullas_count')->label('Patrullas')->counts('patrullas'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (Grupo $record) => $record->puedeBorrarse()),
            ])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return ['index' => ManageGrupos::route('/')];
    }
}
