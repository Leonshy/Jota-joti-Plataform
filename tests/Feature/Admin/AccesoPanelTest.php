<?php

use App\Enums\Rol;
use App\Filament\Pages\Auth\LoginCi;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('al panel entra solo el administrador', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();

    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('los demás roles no entran al panel', function (Rol $rol) {
    $this->actingAs(User::factory()->rol($rol)->create())->get('/admin')->assertForbidden();
})->with([Rol::Beneficiario, Rol::Dirigente, Rol::Tienda]);

it('un admin sin cuenta activada no entra', function () {
    $admin = User::factory()->rol(Rol::Admin)->sinActivar()->create();

    $this->actingAs($admin)->get('/admin')->assertForbidden();
});

it('sin sesión se redirige al login del panel', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->get('/admin/login')->assertOk()->assertSee('Cédula (CI)')->assertDontSee('Correo');
});

it('el admin ingresa con CI y contraseña', function () {
    $admin = User::factory()->rol(Rol::Admin)->create(['pin_hash' => Hash::make('una-contraseña-larga')]);

    Livewire::test(LoginCi::class)
        ->fillForm(['ci' => $admin->ci, 'password' => 'una-contraseña-larga'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($admin);
});

it('con la contraseña incorrecta no entra y el error habla de CI', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();

    Livewire::test(LoginCi::class)
        ->fillForm(['ci' => $admin->ci, 'password' => 'mala'])
        ->call('authenticate')
        ->assertHasErrors(['data.ci']);

    $this->assertGuest();
});

it('un participante con su PIN correcto tampoco entra al panel', function () {
    $beneficiario = User::factory()->create(); // PIN 1234

    Livewire::test(LoginCi::class)
        ->fillForm(['ci' => $beneficiario->ci, 'password' => '1234'])
        ->call('authenticate')
        ->assertHasErrors(['data.ci']);

    $this->assertGuest();
});

it('el panel no carga fuentes de CDN', function () {
    $this->get('/admin/login')->assertOk()->assertDontSee('fonts.bunny.net')->assertDontSee('fonts.googleapis.com');
});

// ── jota:crear-admin ────────────────────────────────────────────────────────

it('crea un admin con una contraseña larga que sirve para el panel', function () {
    $this->artisan('jota:crear-admin', ['ci' => '1234567', 'nombre' => 'Leonardo Prueba'])
        ->expectsQuestion('Contraseña (mínimo 10 caracteres)', 'clave-muy-larga-1')
        ->expectsQuestion('Repetí la contraseña', 'clave-muy-larga-1')
        ->assertSuccessful();

    $admin = User::where('ci', '1234567')->first();
    expect($admin->rol)->toBe(Rol::Admin)->and(Hash::check('clave-muy-larga-1', $admin->pin_hash))->toBeTrue()
        ->and($admin->canAccessPanel(filament()->getPanel('admin')))->toBeTrue();
});

it('rechaza contraseñas cortas o que no coinciden', function () {
    $this->artisan('jota:crear-admin', ['ci' => '1234567', 'nombre' => 'X'])
        ->expectsQuestion('Contraseña (mínimo 10 caracteres)', 'corta')
        ->assertFailed();

    $this->artisan('jota:crear-admin', ['ci' => '1234567', 'nombre' => 'X'])
        ->expectsQuestion('Contraseña (mínimo 10 caracteres)', 'clave-muy-larga-1')
        ->expectsQuestion('Repetí la contraseña', 'otra-clave-larga-2')
        ->assertFailed();

    expect(User::where('ci', '1234567')->exists())->toBeFalse();
});

it('no convierte en admin a un CI existente sin --forzar', function () {
    $persona = User::factory()->create();

    $this->artisan('jota:crear-admin', ['ci' => $persona->ci, 'nombre' => 'X'])->assertFailed();

    expect($persona->fresh()->rol)->toBe(Rol::Beneficiario);
});

it('valida el CI', function () {
    $this->artisan('jota:crear-admin', ['ci' => 'abc', 'nombre' => 'X'])->assertFailed();
});
