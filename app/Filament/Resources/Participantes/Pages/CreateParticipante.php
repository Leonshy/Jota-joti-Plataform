<?php

namespace App\Filament\Resources\Participantes\Pages;

use App\Filament\Resources\Participantes\ParticipanteResource;
use App\Filament\Support\ValidaParticipante;
use Filament\Resources\Pages\CreateRecord;

class CreateParticipante extends CreateRecord
{
    use ValidaParticipante;

    protected static string $resource = ParticipanteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->validarEstructura($data);
    }
}
