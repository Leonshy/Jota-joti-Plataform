<?php

namespace App\Filament\Resources\Auditoria;

use App\Filament\Resources\Auditoria\Pages\ListAuditoria;
use App\Filament\Support\RegistroDeSoloLectura;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/** Auditoría de configuración y de acciones del administrador: quién cambió qué y cuándo (§4.9). */
class ActividadAuditoriaResource extends Resource
{
    use RegistroDeSoloLectura;

    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'auditoria';

    protected static ?string $modelLabel = 'registro de auditoría';

    protected static ?string $pluralModelLabel = 'auditoría';

    protected static ?string $navigationLabel = 'Auditoría';

    protected static string|UnitEnum|null $navigationGroup = 'Registros';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    private const NOMBRES = [
        'configuracion' => 'Configuración',
        'acceso' => 'Acceso',
        'importacion' => 'Importación',
        'purga' => 'Purga de fotos',
    ];

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')->label('Cuándo')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('log_name')->label('Tipo')->badge()
                    ->formatStateUsing(fn (?string $state) => self::NOMBRES[$state] ?? $state),
                TextColumn::make('description')->label('Qué pasó')->searchable()->wrap(),
                TextColumn::make('causer.name')->label('Quién')->placeholder('Sistema'),
                TextColumn::make('sujeto')->label('Sobre')
                    ->state(fn (Activity $record) => $record->subject_type ? class_basename($record->subject_type).' #'.$record->subject_id : '—'),
            ])
            ->filters([
                SelectFilter::make('log_name')->label('Tipo')->options(self::NOMBRES),
            ])
            ->recordActions([
                Action::make('detalle')
                    ->label('Detalle')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (Activity $record) => $record->description)
                    ->modalContent(fn (Activity $record) => view('filament.auditoria-detalle', ['registro' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditoria::route('/')];
    }
}
