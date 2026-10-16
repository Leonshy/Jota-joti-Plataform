<?php

namespace App\Filament\Resources\Participantes\Pages;

use App\Filament\Resources\Participantes\ParticipanteResource;
use App\Filament\Support\ValidaParticipante;
use Filament\Resources\Pages\EditRecord;

class EditParticipante extends EditRecord
{
    use ValidaParticipante;

    protected static string $resource = ParticipanteResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->validarEstructura($data);
    }
}
