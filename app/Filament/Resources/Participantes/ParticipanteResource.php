<?php

namespace App\Filament\Resources\Participantes;

use App\Domain\Acceso\ResetearPin;
use App\Enums\ModoRanking;
use App\Enums\Rol;
use App\Filament\Resources\Participantes\Pages\CreateParticipante;
use App\Filament\Resources\Participantes\Pages\EditParticipante;
use App\Filament\Resources\Participantes\Pages\ListParticipantes;
use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Participantes y demás cuentas. Los saldos (puntos y LisCoins) se muestran pero NUNCA se editan
 * desde acá: solo cambian por los servicios del ledger (CLAUDE.md §2.2).
 */
class ParticipanteResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'participantes';

    protected static ?string $modelLabel = 'persona';

    protected static ?string $pluralModelLabel = 'participantes';

    protected static ?string $navigationLabel = 'Participantes';

    protected static string|UnitEnum|null $navigationGroup = 'Estructura del evento';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $esBeneficiario = fn (Get $get) => ($get('rol') instanceof Rol ? $get('rol') : Rol::tryFrom((string) $get('rol'))) === Rol::Beneficiario;

        return $schema->components([
            Section::make('Datos de la persona')->columns(2)->schema([
                TextInput::make('ci')
                    ->label('Cédula (CI)')->required()->maxLength(15)->inputMode('numeric')
                    ->unique(ignoreRecord: true)
                    ->helperText('Con o sin puntos. La persona crea su PIN con este CI al ingresar por primera vez.'),
                TextInput::make('name')->label('Nombre y apellido')->required()->maxLength(255),
                Select::make('rol')->label('Rol')->options(Rol::class)->required()->native(false)->live()
                    ->default(Rol::Beneficiario)
                    ->disabled(fn (?User $record) => $record?->is(auth()->user()) ?? false)
                    ->helperText('No podés cambiar tu propio rol.'),
            ]),
            Section::make('Ubicación en el evento')->columns(3)->schema([
                Select::make('grupo_id')->label('Grupo')->relationship('grupo', 'nombre')->native(false)->live()
                    ->required($esBeneficiario),
                Select::make('unidad_id')->label('Unidad')->relationship('unidad', 'nombre')->native(false)->live()
                    ->afterStateUpdated(fn ($set) => $set('patrulla_id', null))
                    ->required(fn (Get $get) => in_array($get('rol') instanceof Rol ? $get('rol') : Rol::tryFrom((string) $get('rol')), [Rol::Beneficiario, Rol::Dirigente], true)),
                Select::make('patrulla_id')->label('Patrulla')->native(false)
                    ->options(fn (Get $get) => Patrulla::query()
                        ->when($get('unidad_id'), fn (Builder $q, $unidad) => $q->where('unidad_id', $unidad))
                        ->when($get('grupo_id'), fn (Builder $q, $grupo) => $q->where('grupo_id', $grupo))
                        ->orderBy('nombre')->pluck('nombre', 'id'))
                    ->visible(fn (Get $get) => $esBeneficiario($get) && (Unidad::query()->find($get('unidad_id'))?->modo_ranking !== ModoRanking::Individual))
                    ->helperText('Se listan las patrullas de la unidad y el grupo elegidos.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ci')->label('CI')->searchable()->sortable(),
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('rol')->label('Rol')->badge()->sortable(),
                TextColumn::make('unidad.nombre')->label('Unidad')->sortable(),
                TextColumn::make('patrulla.nombre')->label('Patrulla')->sortable(),
                TextColumn::make('grupo.nombre')->label('Grupo')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('puntos')->label('Puntos')->numeric()->sortable(),
                TextColumn::make('jlis')->label('LisCoins')->numeric()->sortable(),
                IconColumn::make('activado_at')->label('Tiene PIN')->boolean()
                    ->state(fn (User $record) => $record->activado_at !== null),
            ])
            ->filters([
                SelectFilter::make('rol')->label('Rol')->options(Rol::class),
                SelectFilter::make('unidad_id')->label('Unidad')->relationship('unidad', 'nombre'),
                SelectFilter::make('patrulla_id')->label('Patrulla')->relationship('patrulla', 'nombre'),
                TernaryFilter::make('activado')->label('Tiene PIN')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('activado_at'),
                        false: fn (Builder $query) => $query->whereNull('activado_at'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('resetearPin')
                        ->label('Resetear PIN')
                        ->icon(Heroicon::OutlinedKey)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Resetear el PIN')
                        ->modalDescription('Se borra el PIN y se cierran sus sesiones. La persona vuelve a crear su PIN ingresando solo con su CI.')
                        ->action(function (User $record) {
                            app(ResetearPin::class)(auth()->user(), $record);

                            Notification::make()->title("PIN reseteado: {$record->name} ya puede crear uno nuevo con su CI.")->success()->send();
                        }),
                    Action::make('cerrarSesiones')
                        ->label('Cerrar sesiones')
                        ->icon(Heroicon::OutlinedArrowRightStartOnRectangle)
                        ->requiresConfirmation()
                        ->modalDescription('Saca a la persona de todos los dispositivos donde tenga la sesión abierta. No le borra el PIN.')
                        ->action(function (User $record) {
                            $cerradas = DB::table('sessions')->where('user_id', $record->getKey())->delete();
                            $record->forceFill(['remember_token' => null])->save();

                            activity('acceso')->causedBy(auth()->user())->performedOn($record)->log('Cerró todas las sesiones');
                            Notification::make()->title($cerradas > 0 ? "Se cerraron {$cerradas} sesiones." : 'No tenía sesiones abiertas.')->success()->send();
                        }),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParticipantes::route('/'),
            'create' => CreateParticipante::route('/crear'),
            'edit' => EditParticipante::route('/{record}/editar'),
        ];
    }
}
