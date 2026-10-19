<?php

namespace App\Filament\Pages;

use App\Domain\Exportaciones\Csv;
use App\Domain\Exportaciones\Exportaciones;
use App\Domain\Ranking\RankingUnidad;
use App\Enums\ModoRanking;
use App\Models\Unidad;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Rankings con nombres, por equipo e individual, de todas las unidades: para definir los premios (§4.7). */
class RankingsCompletos extends Page
{
    protected string $view = 'filament.pages.rankings-completos';

    protected static ?string $slug = 'rankings';

    protected static ?string $title = 'Rankings completos';

    protected static ?string $navigationLabel = 'Rankings';

    protected static string|UnitEnum|null $navigationGroup = 'Resultados';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function rankings(): array
    {
        return app(RankingUnidad::class)->todas(auth()->user());
    }

    /**
     * Personas con más puntos de cada unidad que se rankea por equipo (en Rovers la tabla principal ya es de personas).
     *
     * @return array<string, list<array{posicion: int, nombre: string, puntaje: int}>>
     */
    public function personasPorUnidad(): array
    {
        $ranking = app(RankingUnidad::class);

        return Unidad::query()->where('modo_ranking', ModoRanking::Equipo)->orderBy('id')->get()
            ->mapWithKeys(fn (Unidad $u) => [$u->nombre => $ranking->personas($u)])->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportarRankings')
                ->label('Exportar rankings (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => Csv::descargar(
                    'rankings-'.now()->format('Y-m-d-His').'.csv',
                    Exportaciones::ENCABEZADOS_RANKING,
                    app(Exportaciones::class)->rankings(auth()->user()),
                )),
            Action::make('exportarSaldos')
                ->label('Exportar saldos (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn () => Csv::descargar(
                    'saldos-'.now()->format('Y-m-d-His').'.csv',
                    Exportaciones::ENCABEZADOS_SALDOS,
                    app(Exportaciones::class)->saldos(auth()->user()),
                )),
        ];
    }
}
