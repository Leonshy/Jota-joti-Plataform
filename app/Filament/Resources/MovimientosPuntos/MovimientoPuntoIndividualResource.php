<?php

namespace App\Filament\Resources\MovimientosPuntos;

use App\Filament\Resources\MovimientosPuntos\Pages\ListMovimientosPuntos;
use App\Filament\Support\OrigenMovimiento;
use App\Filament\Support\RegistroDeSoloLectura;
use App\Models\MovPuntoIndividual;
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
class MovimientoPuntoIndividualResource extends Resource
{
    use RegistroDeSoloLectura;

    protected static ?string $model = MovPuntoIndividual::class;

    protected static ?string $slug = 'movimientos-puntos';

    protected static ?string $modelLabel = 'movimiento de puntos';

    protected static ?string $pluralModelLabel = 'Puntos de personas';

    protected static ?string $navigationLabel = 'Puntos de personas';

    protected static string|UnitEnum|null $navigationGroup = 'Registros';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user:id,name,ci,unidad_id', 'user.unidad:id,nombre', 'registradoPor:id,name']))
            ->columns([
                TextColumn::make('created_at')->label('Cuándo')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('user.name')->label('Persona')->searchable()->sortable(),
                TextColumn::make('user.ci')->label('CI')->searchable(),
                TextColumn::make('user.unidad.nombre')->label('Unidad'),
                TextColumn::make('cantidad')->label('Cantidad')->numeric()->sortable()
                    ->color(fn (int $state) => $state < 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : '').number_format($state, 0, ',', '.')),
                TextColumn::make('origen')->label('Origen')
                    ->state(fn (MovPuntoIndividual $record) => OrigenMovimiento::etiqueta($record->origen_type, $record->origen_id)),
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
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn (Builder $q, $v) => $q->whereHas('user', fn (Builder $u) => $u->where('unidad_id', $v)))),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListMovimientosPuntos::route('/')];
    }
}
