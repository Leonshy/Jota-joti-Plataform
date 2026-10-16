<?php

namespace App\Filament\Resources\Misiones\Pages;

use App\Filament\Resources\Misiones\MisionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMisiones extends ListRecords
{
    protected static string $resource = MisionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva misión')];
    }
}
