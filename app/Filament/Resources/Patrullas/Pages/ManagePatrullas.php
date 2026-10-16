<?php

namespace App\Filament\Resources\Patrullas\Pages;

use App\Filament\Resources\Patrullas\PatrullaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePatrullas extends ManageRecords
{
    protected static string $resource = PatrullaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
