<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

/**
 * Login del panel con CI + PIN (o contraseña), en lugar de email + contraseña: los usuarios no
 * tienen email (CLAUDE.md §2.12). Conserva el límite de intentos de Filament porque acá se entra
 * a administrar todos los saldos.
 */
class LoginCi extends Login
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('ci')
            ->label('Cédula (CI)')
            ->required()
            ->autofocus()
            ->inputMode('numeric')
            ->autocomplete('username');
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('PIN o contraseña')
            ->password()
            ->revealable()
            ->required()
            ->autocomplete('current-password');
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'ci' => trim($data['ci']),
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.ci' => 'CI o PIN incorrectos.',
        ]);
    }
}
