<?php

namespace App\Console\Commands;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

#[AsCommand(name: 'jota:crear-admin', description: 'Crea (o actualiza) un administrador con contraseña propia para el panel')]
class CrearAdmin extends Command
{
    protected $signature = 'jota:crear-admin {ci : Cédula del administrador} {nombre : Nombre y apellido} {--forzar : Convierte en admin a un CI que ya existe con otro rol}';

    public function handle(): int
    {
        $ci = trim($this->argument('ci'));

        if (! preg_match('/^\d{4,12}$/', $ci)) {
            $this->error('El CI debe tener solo números (entre 4 y 12 dígitos).');

            return self::FAILURE;
        }

        $existente = User::query()->where('ci', $ci)->first();

        if ($existente && ! $existente->esAdmin() && ! $this->option('forzar')) {
            $this->error("Ese CI ya pertenece a {$existente->name} con rol {$existente->rol->value}. Usá --forzar para convertirlo en admin.");

            return self::FAILURE;
        }

        // Al admin se le pide una contraseña de verdad (no un PIN de 4 dígitos): maneja todos los saldos.
        $password = $this->secret('Contraseña (mínimo 10 caracteres)');

        if (! is_string($password) || mb_strlen($password) < 10) {
            $this->error('La contraseña debe tener al menos 10 caracteres.');

            return self::FAILURE;
        }

        if ($password !== $this->secret('Repetí la contraseña')) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        $admin = $existente ?? new User(['ci' => $ci]);
        $admin->fill(['name' => $this->argument('nombre'), 'rol' => Rol::Admin]);
        $admin->forceFill(['pin_hash' => $password, 'activado_at' => now()])->save();

        $this->info("Administrador {$admin->name} listo. Ingresa al panel en /admin con su CI y esa contraseña.");

        return self::SUCCESS;
    }
}
