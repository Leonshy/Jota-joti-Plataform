<?php

namespace App\Filament\Resources\Actividades\RelationManagers;

use App\Models\Turno;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TurnosRelationManager extends RelationManager
{
    protected static string $relationship = 'turnos';

    protected static ?string $title = 'Turnos';

    protected static ?string $modelLabel = 'turno';

    protected static ?string $pluralModelLabel = 'turnos';

    public function form(Schema $schema): Schema
    {
        // Con reservas, mover el horario podría hacer que se pisen sin que nadie lo valide: primero se cancelan.
        $conReservas = fn (?Turno $record) => $record?->tieneReservas() ?? false;

        return $schema->components([
            DateTimePicker::make('inicio')->label('Empieza')->required()->seconds(false)->disabled($conReservas),
            DateTimePicker::make('fin')->label('Termina')->required()->seconds(false)->after('inicio')->disabled($conReservas)
                ->helperText('Con reservas hechas no se puede mover el horario: cancelalas primero.'),
            TextInput::make('cupo')->label('Cupo')->numeric()->integer()->minValue(1)->maxValue(10000)
                // En la estación JOTA cada horario es de un solo equipo: no se elige cupo. En los talleres es obligatorio.
                ->hidden(fn () => $this->getOwnerRecord()->esEstacion())
                ->required(fn () => ! $this->getOwnerRecord()->esEstacion())
                ->helperText(fn () => $this->getOwnerRecord()->esPorEquipo()
                    ? 'Cuántas patrullas pueden apuntarse a este turno.'
                    : 'Cuántas personas pueden apuntarse a este turno.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('reservas'))
            ->columns([
                TextColumn::make('inicio')->label('Empieza')->dateTime('d/m H:i')->sortable(),
                TextColumn::make('fin')->label('Termina')->dateTime('d/m H:i')->sortable(),
                TextColumn::make('cupo')->label('Cupo')->formatStateUsing(fn ($state) => $this->getOwnerRecord()->esEstacion() ? '1 equipo' : $state)->placeholder('Sin tope (turno viejo)'),
                TextColumn::make('reservas_count')->label('Reservas')->numeric(),
            ])
            ->headerActions([CreateAction::make()->label('Nuevo turno')])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (Turno $record) => ! $record->tieneReservas()),
            ])
            ->defaultSort('inicio');
    }
}
