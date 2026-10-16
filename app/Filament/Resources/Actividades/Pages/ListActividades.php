<?php

namespace App\Filament\Resources\Actividades\Pages;

use App\Filament\Resources\Actividades\ActividadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActividades extends ListRecords
{
    protected static string $resource = ActividadResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva actividad')];
    }
}
