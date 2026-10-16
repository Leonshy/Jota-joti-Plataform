<?php

namespace App\Filament\Resources\Patrullas;

use App\Enums\ModoRanking;
use App\Filament\Resources\Patrullas\Pages\ManagePatrullas;
use App\Models\Patrulla;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class PatrullaResource extends Resource
{
    protected static ?string $model = Patrulla::class;

    protected static ?string $modelLabel = 'patrulla';

    protected static ?string $pluralModelLabel = 'patrullas';

    protected static string|UnitEnum|null $navigationGroup = 'Estructura del evento';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        // Con integrantes, mover la patrulla de grupo o de unidad dejaría a las personas en una
        // patrulla que no es de su grupo o unidad.
        $conIntegrantes = fn (?Patrulla $record) => $record?->integrantes()->exists() ?? false;

        return $schema->components([
            Select::make('unidad_id')
                ->label('Unidad')
                ->relationship('unidad', 'nombre', fn (Builder $query) => $query->where('modo_ranking', ModoRanking::Equipo))
                ->required()->native(false)->live()
                ->disabled($conIntegrantes)
                ->helperText('Solo las unidades que se rankean por equipo tienen patrullas.'),
            Select::make('grupo_id')
                ->label('Grupo')
                ->relationship('grupo', 'nombre')
                ->required()->native(false)->live()
                ->disabled($conIntegrantes),
            TextInput::make('nombre')
                ->label('Nombre de la patrulla')
                ->required()->maxLength(255)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('unidad_id', $get('unidad_id'))->where('grupo_id', $get('grupo_id')),
                )
                ->validationMessages(['unique' => 'Ya hay una patrulla con ese nombre en esa unidad y grupo.']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Patrulla')->searchable()->sortable(),
                TextColumn::make('unidad.nombre')->label('Unidad')->sortable(),
                TextColumn::make('grupo.nombre')->label('Grupo')->sortable(),
                TextColumn::make('integrantes_count')->label('Integrantes')->counts('integrantes')->sortable(),
                TextColumn::make('puntos')->label('Puntos de la patrulla')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('unidad_id')->label('Unidad')->relationship('unidad', 'nombre'),
                SelectFilter::make('grupo_id')->label('Grupo')->relationship('grupo', 'nombre'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (Patrulla $record) => $record->puedeBorrarse()),
            ])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return ['index' => ManagePatrullas::route('/')];
    }
}
