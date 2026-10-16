<?php

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('arranca la aplicación y manda a ingresar a quien no tiene sesión', function () {
    $this->get('/')->assertRedirect(route('ingresar'));
});

it('usa la hora de Asunción y el idioma español', function () {
    expect(config('app.timezone'))->toBe('America/Asuncion')
        ->and(config('app.locale'))->toBe('es');
});

it('tiene todas las tablas del modelo de datos', function (string $tabla) {
    expect(Schema::hasTable($tabla))->toBeTrue();
})->with([
    'grupos', 'unidades', 'patrullas', 'users', 'misiones', 'mision_unidad', 'entregas',
    'entrega_imagenes', 'actividades', 'actividad_unidad', 'turnos', 'reservas',
    'sesiones_asistencia', 'asistencias', 'cumplimientos_equipo',
    'mov_puntos_individuales', 'mov_puntos_patrulla', 'mov_jlis', 'cobros',
]);

it('autentica con pin_hash en lugar de password', function () {
    $user = User::factory()->rol(Rol::Dirigente)->create();

    expect($user->getAuthPassword())->toBe($user->pin_hash)
        ->and($user->rol)->toBe(Rol::Dirigente)
        ->and($user->jlis)->toBe(0);
});
