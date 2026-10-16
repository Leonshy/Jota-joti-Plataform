<?php

namespace App\Filament\Resources\Entregas;

use App\Domain\Misiones\AprobarEntrega;
use App\Domain\Misiones\EntregaYaRevisada;
use App\Domain\Misiones\RechazarEntrega;
use App\Enums\EstadoEntrega;
use App\Filament\Resources\Entregas\Pages\ListEntregas;
use App\Models\Entrega;
use App\Models\Unidad;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Validación global: el administrador revisa entregas de cualquier unidad (§4.8). */
class EntregaResource extends Resource
{
    protected static ?string $model = Entrega::class;

    protected static ?string $slug = 'entregas';

    protected static ?string $modelLabel = 'entrega';

    protected static ?string $pluralModelLabel = 'entregas';

    protected static ?string $navigationLabel = 'Validación de entregas';

    protected static string|UnitEnum|null $navigationGroup = 'Juego';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

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
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pendientes = Entrega::query()->pendientes()->count();

        return $pendientes > 0 ? (string) $pendientes : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['mision:id,titulo,puntos,modalidad', 'user:id,name,unidad_id', 'user.unidad:id,nombre', 'patrulla:id,nombre', 'revisor:id,name'])
                ->withCount('imagenes'))
            ->poll('30s')
            ->columns([
                TextColumn::make('created_at')->label('Entregada')->since()->sortable(),
                TextColumn::make('mision.titulo')->label('Misión')->searchable()->wrap(),
                TextColumn::make('user.name')->label('Entregó')->searchable(),
                TextColumn::make('user.unidad.nombre')->label('Unidad'),
                TextColumn::make('patrulla.nombre')->label('Patrulla')->placeholder('—'),
                TextColumn::make('mision.puntos')->label('Puntos')->numeric(),
                TextColumn::make('imagenes_count')->label('Fotos')->numeric(),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->color(fn (EstadoEntrega $state) => match ($state) {
                        EstadoEntrega::Pendiente => 'warning',
                        EstadoEntrega::Aprobada => 'success',
                        EstadoEntrega::Rechazada => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(EstadoEntrega::class)->default(EstadoEntrega::Pendiente->value),
                SelectFilter::make('unidad')->label('Unidad')->options(fn () => Unidad::query()->orderBy('nombre')->pluck('nombre', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn (Builder $q, $unidad) => $q->whereHas('user', fn (Builder $u) => $u->where('unidad_id', $unidad)))),
                SelectFilter::make('mision_id')->label('Misión')->relationship('mision', 'titulo'),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (Entrega $record) => $record->mision->titulo)
                    ->modalContent(fn (Entrega $record) => view('filament.entrega-detalle', ['entrega' => $record->load(['user.unidad', 'patrulla', 'imagenes', 'revisor'])]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Entrega $record) => $record->estado === EstadoEntrega::Pendiente)
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar la entrega')
                    ->modalDescription(fn (Entrega $record) => $record->mision->esPorPatrulla()
                        ? 'Se acreditan '.cantidad($record->mision->puntos, 'punto').' a la patrulla y LisCoins a cada integrante.'
                        : 'Se acreditan '.cantidad($record->mision->puntos, 'punto').' y '.cantidad($record->mision->puntos, 'jli').' a la persona.')
                    ->action(function (Entrega $record) {
                        try {
                            app(AprobarEntrega::class)(auth()->user(), $record);
                            Notification::make()->title('Entrega aprobada.')->success()->send();
                        } catch (EntregaYaRevisada) {
                            Notification::make()->title('Esa entrega ya fue revisada por otra persona.')->warning()->send();
                        }
                    }),
                Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Entrega $record) => $record->estado === EstadoEntrega::Pendiente)
                    ->modalHeading('Rechazar la entrega')
                    ->schema([
                        Textarea::make('motivo')->label('Motivo (lo ve quien entregó)')->required()->maxLength(500)->rows(3),
                    ])
                    ->action(function (Entrega $record, array $data) {
                        try {
                            app(RechazarEntrega::class)(auth()->user(), $record, $data['motivo']);
                            Notification::make()->title('Entrega rechazada.')->success()->send();
                        } catch (EntregaYaRevisada) {
                            Notification::make()->title('Esa entrega ya fue revisada por otra persona.')->warning()->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'asc');
    }

    public static function getPages(): array
    {
        return ['index' => ListEntregas::route('/')];
    }
}
