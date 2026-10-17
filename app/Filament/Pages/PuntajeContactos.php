<?php

namespace App\Filament\Pages;

use App\Domain\Contactos\ContactoInvalido;
use App\Domain\Contactos\Puntaje;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Cuántos puntos vale un contacto validado: JOTA y JOTI, cada uno local (Paraguay) o del exterior. Mientras un puntaje no
 * esté definido, los dirigentes no pueden aprobar contactos de ese tipo.
 *
 * @property-read Schema $form
 */
class PuntajeContactos extends Page
{
    protected string $view = 'filament.pages.puntaje-contactos';

    protected static ?string $slug = 'puntaje-contactos';

    protected static ?string $title = 'Puntaje de los contactos';

    protected static ?string $navigationLabel = 'Puntaje de contactos';

    protected static string|UnitEnum|null $navigationGroup = 'Juego';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(app(Puntaje::class)->todos());
    }

    public function form(Schema $schema): Schema
    {
        $campo = fn (string $clave) => TextInput::make($clave)->label(Puntaje::claves()[$clave])->numeric()->integer()
            ->required()->minValue(0)->maxValue(100000)->suffix('puntos');

        return $schema->components([
            Form::make([
                Section::make('JOTA (radio)')->description('Suma a la patrulla; en los Rovers, a cada Rover. Es local si el indicativo empieza con ZP.')->columns(2)->schema([
                    $campo('jota_local'),
                    $campo('jota_exterior'),
                ]),
                Section::make('JOTI (internet)')->description('Suma a la persona. Es local si el país es Paraguay.')->columns(2)->schema([
                    $campo('joti_local'),
                    $campo('joti_exterior'),
                ]),
            ])->livewireSubmitHandler('save')->footer([
                Actions::make([Action::make('save')->label('Guardar puntajes')->submit('save')->keyBindings(['mod+s'])]),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        try {
            app(Puntaje::class)->guardar($this->form->getState(), auth()->user());
        } catch (ContactoInvalido $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title('Puntajes guardados')->body('Los LisCoins que se acreditan son el mismo monto.')->send();
    }
}
