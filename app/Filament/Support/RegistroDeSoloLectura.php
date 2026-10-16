<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Model;

/** Recursos que solo se consultan: los registros contables son de solo agregar (CLAUDE.md §2.1). */
trait RegistroDeSoloLectura
{
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
