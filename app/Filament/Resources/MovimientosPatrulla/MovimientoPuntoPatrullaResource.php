<?php

namespace App\Filament\Resources\MovimientosPatrulla;

use App\Filament\Resources\MovimientosPatrulla\Pages\ListMovimientosPatrulla;
use App\Filament\Support\OrigenMovimiento;
use App\Filament\Support\RegistroDeSoloLectura;
use App\Models\MovPuntoPatrulla;
use App\Models\Unidad;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Historial de solo lectura: quién, qué, cuándo y por qué (§4.9). */
class MovimientoPuntoPatrullaResource extends Resource
{
    use RegistroDeSoloLectura;

    protected static ?string $model = MovPuntoPatrulla::class;

    protected static ?string $slug = 'movimientos-patrullas';

    protected static ?string $modelLabel = 'movimiento de puntos de patrulla';

    protected static ?string $pluralModelLabel = 'Puntos de patrullas';

    protected static ?string $navigationLabel = 'Puntos de patrullas';

    protected static string|UnitEnum|null $navigationGroup = 'Registros';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['patrulla:id,nombre,unidad_id', 'patrulla.unidad:id,nombre', 'registradoPor:id,name']))
            ->columns([
                TextColumn::make('created_at')->label('Cuándo')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('patrulla.nombre')->label('Patrulla')->searchable()->sortable(),
                TextColumn::make('patrulla.unidad.nombre')->label('Unidad'),
                TextColumn::make('cantidad')->label('Cantidad')->numeric()->sortable()
                    ->color(fn (int $state) => $state < 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : '').number_format($state, 0, ',', '.')),
                TextColumn::make('origen')->label('Origen')
                    ->state(fn (MovPuntoPatrulla $record) => OrigenMovimiento::etiqueta($record->origen_type, $record->origen_id)),
                TextColumn::make('motivo')->label('Motivo')->wrap()->limit(80)->placeholder('—'),
                TextColumn::make('registradoPor.name')->label('Registró')->placeholder('Sistema'),
            ])
            ->filters([
                TernaryFilter::make('signo')->label('Tipo')->trueLabel('Solo sumas')->falseLabel('Solo restas')
                    ->queries(
                        true: fn (Builder $query) => $query->where('cantidad', '>', 0),
                        false: fn (Builder $query) => $query->where('cantidad', '<', 0),
                    ),
                SelectFilter::make('unidad')->label('Unidad')
                    ->options(fn () => Unidad::query()->orderBy('nombre')->pluck('nombre', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn (Builder $q, $v) => $q->whereHas('patrulla', fn (Builder $u) => $u->where('unidad_id', $v)))),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListMovimientosPatrulla::route('/')];
    }
}
