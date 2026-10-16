<?php

namespace App\Filament\Resources\Reservas;

use App\Domain\Actividades\CancelarReserva;
use App\Filament\Resources\Reservas\Pages\ListReservas;
use App\Models\Actividad;
use App\Models\Reserva;
use App\Models\Unidad;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Reservas de turnos de todas las unidades. Cancelar pasa por la acción de dominio (libera el cupo). */
class ReservaResource extends Resource
{
    protected static ?string $model = Reserva::class;

    protected static ?string $slug = 'reservas';

    protected static ?string $modelLabel = 'reserva';

    protected static ?string $pluralModelLabel = 'reservas';

    protected static ?string $navigationLabel = 'Reservas de turnos';

    protected static string|UnitEnum|null $navigationGroup = 'Juego';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false; // se cancela con la acción de abajo, que pasa por el dominio
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->select('reservas.*')
                ->join('turnos', 'turnos.id', '=', 'reservas.turno_id')
                ->with(['turno.actividad:id,titulo', 'user:id,name,unidad_id,patrulla_id', 'user.unidad:id,nombre', 'user.patrulla:id,nombre', 'patrulla:id,nombre,unidad_id', 'patrulla.unidad:id,nombre', 'unidad:id,nombre']))
            ->poll('30s')
            ->columns([
                TextColumn::make('turno.inicio')->label('Turno')->dateTime('d/m H:i')->sortable(query: fn (Builder $q, string $dir) => $q->orderBy('turnos.inicio', $dir)),
                TextColumn::make('turno.actividad.titulo')->label('Actividad')->wrap(),
                TextColumn::make('quien')->label('Reservó')->state(fn (Reserva $r) => match (true) {
                    $r->unidad_id !== null => 'Toda la unidad '.$r->unidad->nombre,
                    $r->patrulla_id !== null => 'Patrulla '.$r->patrulla->nombre,
                    default => $r->user->name,
                })
                    ->searchable(query: fn (Builder $q, string $s) => $q->where(fn (Builder $b) => $b
                        ->whereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$s}%")->orWhere('ci', 'like', "%{$s}%"))
                        ->orWhereHas('patrulla', fn (Builder $p) => $p->where('nombre', 'like', "%{$s}%"))
                        ->orWhereHas('unidad', fn (Builder $x) => $x->where('nombre', 'like', "%{$s}%")))),
                TextColumn::make('unidad')->label('Unidad')->state(fn (Reserva $r) => match (true) {
                    $r->unidad_id !== null => $r->unidad->nombre,
                    $r->patrulla_id !== null => $r->patrulla->unidad?->nombre,
                    default => $r->user->unidad?->nombre,
                }),
            ])
            ->filters([
                SelectFilter::make('actividad')->label('Actividad')->options(fn () => Actividad::query()->orderBy('titulo')->pluck('titulo', 'id'))
                    ->query(fn (Builder $q, array $data) => $q->when($data['value'], fn (Builder $b, $id) => $b->where('turnos.actividad_id', $id))),
                SelectFilter::make('unidad')->label('Unidad')->options(fn () => Unidad::query()->orderBy('nombre')->pluck('nombre', 'id'))
                    ->query(fn (Builder $q, array $data) => $q->when($data['value'], fn (Builder $b, $id) => $b
                        ->where(fn (Builder $w) => $w->whereHas('user', fn (Builder $u) => $u->where('unidad_id', $id))
                            ->orWhereHas('patrulla', fn (Builder $p) => $p->where('unidad_id', $id))
                            ->orWhere('reservas.unidad_id', $id)))),
            ])
            ->recordActions([
                Action::make('cancelar')
                    ->label('Cancelar reserva')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar la reserva')
                    ->modalDescription('Se libera el lugar del turno. Esta acción se puede hacer aunque el turno ya haya empezado.')
                    ->action(function (Reserva $record) {
                        app(CancelarReserva::class)(auth()->user(), $record);
                        Notification::make()->title('Reserva cancelada.')->success()->send();
                    }),
            ])
            ->defaultSort('turnos.inicio', 'asc');
    }

    public static function getPages(): array
    {
        return ['index' => ListReservas::route('/')];
    }
}
