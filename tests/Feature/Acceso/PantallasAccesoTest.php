<?php

use App\Domain\Acceso\DestinoPorRol;
use App\Enums\Rol;
use App\Livewire\Acceso\Activar;
use App\Livewire\Acceso\Ingresar;
use App\Models\Unidad;
use App\Models\User;
use Livewire\Livewire;

// ── Ingreso ─────────────────────────────────────────────────────────────────

it('muestra la pantalla de ingreso a quien no tiene sesión', function () {
    $this->get(route('ingresar'))->assertOk()->assertSee(__('jota.acceso.ingresar_boton'));
});

it('cada rol entra a su pantalla', function (Rol $rol, string $destino) {
    $user = User::factory()->rol($rol)->create();

    Livewire::test(Ingresar::class)
        ->set('ci', $user->ci)->set('pin', '1234')->call('ingresar')
        ->assertHasNoErrors()->assertRedirect($destino === '/admin' ? url('/admin') : route($destino));

    $this->assertAuthenticatedAs($user);
})->with([
    'beneficiario' => [Rol::Beneficiario, 'app.inicio'],
    'dirigente' => [Rol::Dirigente, 'dirigente.inicio'],
    'tienda' => [Rol::Tienda, 'tienda.inicio'],
    'admin' => [Rol::Admin, '/admin'],
]);

it('acepta el CI con puntos y espacios', function () {
    $user = User::factory()->create(['ci' => '4123456']);

    Livewire::test(Ingresar::class)
        ->set('ci', ' 4.123.456 ')->set('pin', '1234')->call('ingresar')
        ->assertHasNoErrors()->assertRedirect(route('app.inicio'));
});

it('con PIN incorrecto avisa, vacía el PIN y no inicia sesión', function () {
    $user = User::factory()->create();

    Livewire::test(Ingresar::class)
        ->set('ci', $user->ci)->set('pin', '0000')->call('ingresar')
        ->assertHasErrors(['pin'])->assertSet('pin', '')->assertNoRedirect();

    $this->assertGuest();
});

it('con un CI que no está cargado avisa en el campo del CI', function () {
    Livewire::test(Ingresar::class)
        ->set('ci', '9999999')->set('pin', '1234')->call('ingresar')
        ->assertHasErrors(['ci']);
});

it('valida que el CI y el PIN estén completos', function () {
    Livewire::test(Ingresar::class)->set('ci', '')->set('pin', '1234')->call('ingresar')->assertHasErrors(['ci']);
    Livewire::test(Ingresar::class)->set('ci', '4000001')->set('pin', '12')->call('ingresar')->assertHasErrors(['pin']);
});

it('quien todavía no creó su PIN pasa a crearlo con el CI ya escrito', function () {
    $user = User::factory()->sinActivar()->create();

    Livewire::test(Ingresar::class)
        ->set('ci', $user->ci)->set('pin', '1234')->call('ingresar')
        ->assertRedirect(route('activar', ['ci' => $user->ci]));

    $this->assertGuest();
});

it('se puede fallar tantas veces como haga falta', function () {
    $user = User::factory()->create();
    $c = Livewire::test(Ingresar::class)->set('ci', $user->ci);

    foreach (range(1, 15) as $_) {
        $c->set('pin', '0000')->call('ingresar')->assertHasErrors(['pin']);
    }

    $c->set('pin', '1234')->call('ingresar')->assertRedirect(route('app.inicio'));
});

it('después de ingresar vuelve a donde iba (p. ej. un QR escaneado con la cámara)', function () {
    $user = User::factory()->create();
    $destino = url('/asistencia/7/registrar/abc');

    $this->get($destino)->assertRedirect(route('ingresar'));
    session(['url.intended' => $destino]);

    Livewire::test(Ingresar::class)
        ->set('ci', $user->ci)->set('pin', '1234')->call('ingresar')
        ->assertRedirect($destino);
});

it('quien ya tiene sesión no ve el ingreso: lo mandan a su pantalla', function () {
    $this->actingAs(User::factory()->rol(Rol::Tienda)->create())
        ->get(route('ingresar'))->assertRedirect(route('tienda.inicio'));
});

// ── Activación ──────────────────────────────────────────────────────────────

it('quien activa su cuenta al escanear un QR vuelve a ese QR y no al inicio', function () {
    $user = User::factory()->sinActivar()->create();
    $destino = url('/asistencia/7/registrar/abc');

    $this->get($destino)->assertRedirect(route('ingresar'));

    Livewire::test(Activar::class)
        ->set('ci', $user->ci)->call('continuar')
        ->set('pin', '4321')->set('pinRepetido', '4321')->call('crear')
        ->assertRedirect($destino);
});

it('crea el PIN en dos pasos y entra con la bienvenida', function () {
    $user = User::factory()->sinActivar()->create(['name' => 'Ana María Benítez']);

    Livewire::test(Activar::class)
        ->set('ci', $user->ci)->call('continuar')->assertSet('paso', 2)
        ->set('pin', '4321')->set('pinRepetido', '4321')->call('crear')
        ->assertHasNoErrors()->assertRedirect(route('app.inicio'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->activado_at)->not->toBeNull()
        ->and(session('bienvenida'))->toBe('Ana');
});

it('precarga el CI que viene de la pantalla de ingreso', function () {
    $user = User::factory()->sinActivar()->create();

    $this->get(route('activar', ['ci' => $user->ci]))->assertOk()->assertSee($user->ci);
});

it('no deja crear el PIN si los dos no coinciden o no son 4 números', function () {
    $user = User::factory()->sinActivar()->create();
    $c = Livewire::test(Activar::class)->set('ci', $user->ci)->call('continuar');

    $c->set('pin', '1234')->set('pinRepetido', '1235')->call('crear')->assertHasErrors(['pinRepetido']);
    $c->set('pin', '12ab')->set('pinRepetido', '12ab')->call('crear')->assertHasErrors(['pin']);

    expect($user->fresh()->activado_at)->toBeNull();
    $this->assertGuest();
});

it('avisa si el CI no está cargado o ya tiene PIN', function () {
    $activo = User::factory()->create();

    Livewire::test(Activar::class)->set('ci', '9999999')->call('continuar')->assertHasErrors(['ci'])->assertSet('paso', 1);
    Livewire::test(Activar::class)->set('ci', $activo->ci)->call('continuar')->assertHasErrors(['ci'])->assertSet('paso', 1);
});

it('si otra persona activó la cuenta mientras tanto, vuelve al paso 1 sin pisar el PIN', function () {
    $user = User::factory()->sinActivar()->create();
    $c = Livewire::test(Activar::class)->set('ci', $user->ci)->call('continuar');

    User::whereKey($user)->update(['pin_hash' => bcrypt('9999'), 'activado_at' => now()]);

    $c->set('pin', '1111')->set('pinRepetido', '1111')->call('crear')->assertHasErrors(['ci'])->assertSet('paso', 1);
    $this->assertGuest();
});

it('se puede volver del paso 2 al paso 1', function () {
    $user = User::factory()->sinActivar()->create();

    Livewire::test(Activar::class)->set('ci', $user->ci)->call('continuar')->set('pin', '1234')->call('volver')
        ->assertSet('paso', 1)->assertSet('pin', '');
});

// ── Salir, raíz y roles ─────────────────────────────────────────────────────

it('cierra la sesión solo por POST', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/salir')->assertStatus(405);
    $this->assertAuthenticated();

    $this->post(route('salir'))->assertRedirect(route('ingresar'));
    $this->assertGuest();
});

it('la raíz manda a cada uno a su lugar', function () {
    $this->get('/')->assertRedirect(route('ingresar'));

    $user = User::factory()->rol(Rol::Dirigente)->create();
    $this->actingAs($user)->get('/')->assertRedirect(DestinoPorRol::para($user));
});

it('cada rol solo entra a sus pantallas', function (Rol $rol, string $ruta, int $estado) {
    $this->actingAs(User::factory()->rol($rol)->create(['unidad_id' => Unidad::factory()->create()->id]))->get(route($ruta))->assertStatus($estado);
})->with([
    'beneficiario a lo suyo' => [Rol::Beneficiario, 'app.inicio', 200],
    'beneficiario al dirigente' => [Rol::Beneficiario, 'dirigente.inicio', 403],
    'beneficiario a la tienda' => [Rol::Beneficiario, 'tienda.inicio', 403],
    'dirigente a lo suyo' => [Rol::Dirigente, 'dirigente.inicio', 200],
    'dirigente al participante' => [Rol::Dirigente, 'app.inicio', 403],
    'tienda a lo suyo' => [Rol::Tienda, 'tienda.inicio', 200],
    'tienda al dirigente' => [Rol::Tienda, 'dirigente.asistencia', 403],
    'admin al participante' => [Rol::Admin, 'app.inicio', 403],
]);

it('sin sesión, las pantallas de la app piden ingresar', function (string $ruta) {
    $this->get(route($ruta))->assertRedirect(route('ingresar'));
})->with(['app.inicio', 'app.ranking', 'dirigente.inicio', 'tienda.inicio']);
