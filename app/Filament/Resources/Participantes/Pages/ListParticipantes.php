<?php

namespace App\Filament\Resources\Participantes\Pages;

use App\Domain\Exportaciones\Csv;
use App\Domain\Exportaciones\Exportaciones;
use App\Filament\Pages\ImportarParticipantes;
use App\Filament\Resources\Participantes\ParticipanteResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListParticipantes extends ListRecords
{
    protected static string $resource = ParticipanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva persona'),
            Action::make('importar')
                ->label('Importar CSV')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->url(ImportarParticipantes::getUrl()),
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
