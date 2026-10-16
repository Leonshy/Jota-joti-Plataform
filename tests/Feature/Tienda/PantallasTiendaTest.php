<?php

use App\Domain\Tienda\CancelarCobro;
use App\Domain\Tienda\CobroVencido;
use App\Domain\Tienda\CobroYaPagado;
use App\Domain\Tienda\CrearCobro;
use App\Domain\Tienda\PagarCobro;
use App\Enums\Rol;
use App\Livewire\Tienda\Cobrar;
use App\Livewire\Tienda\Devolver;
use App\Models\Cobro;
use App\Models\Devolucion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->tienda = usuarioTienda();
    $this->yo = conJlis(100);
    $this->yo->update(['name' => 'Ana Benítez']);
});

// ── Cobrar ──────────────────────────────────────────────────────────────────

it('genera el cobro con el monto tecleado y muestra el QR', function () {
    $c = Livewire::actingAs($this->tienda)->test(Cobrar::class)->set('monto', '30')->call('generar')
        ->assertSet('monto', '')->assertSee(__('jota.escanear.qr_alt'))->assertSee('30');

    $cobro = Cobro::sole();
    expect($cobro->monto)->toBe(30)->and($cobro->creado_por)->toBe($this->tienda->id)->and($cobro->estado())->toBe(Cobro::PENDIENTE);
    $c->assertSee('wire:poll.2s', false);
});

it('valida el monto: vacío, cero o por encima del tope', function () {
    $c = Livewire::actingAs($this->tienda)->test(Cobrar::class);

    $c->set('monto', '')->call('generar')->assertSet('error', __('jota.tienda.error_monto'));
    $c->set('monto', '0')->call('generar')->assertSet('error', __('jota.tienda.error_monto'));
    $c->set('monto', '5000000')->call('generar')->assertSet('error', fn ($e) => $e !== '');

    expect(Cobro::count())->toBe(0);
});

it('cuando la persona paga, la pantalla de la tienda muestra quién pagó', function () {
    $c = Livewire::actingAs($this->tienda)->test(Cobrar::class)->set('monto', '30')->call('generar');

    app(PagarCobro::class)($this->yo, Cobro::sole()->token);

    $c->call('$refresh')->assertSee(__('jota.tienda.pagado_por', ['nombre' => 'Ana Benítez']))->assertDontSee(__('jota.escanear.qr_alt'));
    $c->call('nuevo')->assertSet('cobroId', null);
});

it('un cobro vencido muestra el aviso y deja generar otro', function () {
    $c = Livewire::actingAs($this->tienda)->test(Cobrar::class)->set('monto', '30')->call('generar');

    $this->travel(3)->minutes();

    $c->call('$refresh')->assertSee(__('jota.tienda.vencido_titulo'));
});

it('cancelar el cobro hace que el QR deje de servir', function () {
    $c = Livewire::actingAs($this->tienda)->test(Cobrar::class)->set('monto', '30')->call('generar');
    $token = Cobro::sole()->token;

    $c->call('cancelar')->assertSet('cobroId', null);

    expect(fn () => app(PagarCobro::class)($this->yo, $token))->toThrow(CobroVencido::class);
    expect($this->yo->fresh()->jlis)->toBe(100);
});

it('cancelar un cobro ya pagado no lo deshace', function () {
    $cobro = app(CrearCobro::class)($this->tienda, 30);
    app(PagarCobro::class)($this->yo, $cobro->token);

    expect(fn () => app(CancelarCobro::class)($this->tienda, $cobro))->toThrow(CobroYaPagado::class);
    expect($this->yo->fresh()->jlis)->toBe(70);
});

it('otra tienda no puede cancelar un cobro ajeno', function () {
    $cobro = app(CrearCobro::class)($this->tienda, 30);

    expect(fn () => app(CancelarCobro::class)(usuarioTienda(), $cobro))->toThrow(AuthorizationException::class);
    expect($cobro->fresh()->estado())->toBe(Cobro::PENDIENTE);
});

// ── Pagar (participante) ────────────────────────────────────────────────────

it('escanear el QR de cobro muestra la confirmación, y pagar muestra el resultado', function () {
    $cobro = app(CrearCobro::class)($this->tienda, 30);

    $this->actingAs($this->yo)->get(route('cobro.ver', $cobro->token))->assertOk()
        ->assertSee(__('jota.pago.confirmar_titulo'))->assertSee('30')->assertSee($this->tienda->name);

    $this->post(route('cobro.pagar', $cobro->token))->assertOk()->assertSee(__('jota.pago.ok_titulo'));
    expect($this->yo->fresh()->jlis)->toBe(70);
});

it('sin saldo suficiente avisa cuánto falta y no descuenta', function () {
    $cobro = app(CrearCobro::class)($this->tienda, 500);

    $this->actingAs($this->yo)->get(route('cobro.ver', $cobro->token))->assertOk()->assertSee(__('jota.pago.error_saldo', ['faltan' => cantidad(400, 'jli')]));
    $this->post(route('cobro.pagar', $cobro->token))->assertStatus(422)->assertSee(__('jota.pago.saldo_titulo'));
    expect($this->yo->fresh()->jlis)->toBe(100);
});

it('cobro vencido, ya pagado o inexistente muestran su pantalla', function () {
    $pagado = app(CrearCobro::class)($this->tienda, 10);
    app(PagarCobro::class)(conJlis(50), $pagado->token);
    $viejo = app(CrearCobro::class)($this->tienda, 10);
    $this->travel(3)->minutes();

    $this->actingAs($this->yo);
    $this->get(route('cobro.ver', $pagado->token))->assertSee(__('jota.pago.ya_pagado_titulo'));
    $this->get(route('cobro.ver', $viejo->token))->assertStatus(422)->assertSee(__('jota.pago.vencido_titulo'));
    $this->get(route('cobro.ver', 'nada'))->assertNotFound()->assertSee(__('jota.pago.no_existe_titulo'));
});

it('el QR de cobro sin sesión pide ingresar y vuelve al cobro', function () {
    $cobro = app(CrearCobro::class)($this->tienda, 30);

    $this->get(route('cobro.ver', $cobro->token))->assertRedirect(route('ingresar'));
});

it('quien no es participante no paga', function () {
    $cobro = app(CrearCobro::class)($this->tienda, 30);

    $this->actingAs(User::factory()->rol(Rol::Dirigente)->create())->get(route('cobro.ver', $cobro->token))->assertForbidden();
});

// ── Devolver ────────────────────────────────────────────────────────────────

function gastarJlis($t, int $monto): void
{
    app(PagarCobro::class)($t->yo, app(CrearCobro::class)($t->tienda, $monto)->token);
}

it('busca a la persona, muestra gasto, devuelto y tope, y devuelve', function () {
    gastarJlis($this, 40);

    $c = Livewire::actingAs($this->tienda)->test(Devolver::class)->set('buscar', $this->yo->ci)->assertSee('Ana Benítez')
        ->call('elegir', $this->yo->id)->assertSee('40')
        ->set('monto', '15')->set('motivo', 'Producto en mal estado')->call('devolver')->assertHasNoErrors()
        ->assertSee(__('jota.tienda.devuelto_titulo'));

    expect($this->yo->fresh()->jlis)->toBe(75)->and(Devolucion::sole()->monto)->toBe(15);
    $c->call('elegir', $this->yo->id)->assertSee('25'); // tope restante: 40 − 15
});

it('no devuelve más que el tope y avisa', function () {
    gastarJlis($this, 40);

    Livewire::actingAs($this->tienda)->test(Devolver::class)->call('elegir', $this->yo->id)
        ->set('monto', '50')->set('motivo', 'x')->call('devolver')->assertHasErrors(['monto']);

    expect(Devolucion::count())->toBe(0)->and($this->yo->fresh()->jlis)->toBe(60);
});

it('quien no gastó nada no tiene nada para devolver', function () {
    Livewire::actingAs($this->tienda)->test(Devolver::class)->call('elegir', $this->yo->id)->assertSee(__('jota.tienda.nada_titulo'));
});

it('valida monto y motivo', function () {
    gastarJlis($this, 40);
    $c = Livewire::actingAs($this->tienda)->test(Devolver::class)->call('elegir', $this->yo->id);

    $c->set('monto', 'abc')->set('motivo', 'x')->call('devolver')->assertHasErrors(['monto']);
    $c->set('monto', '5')->set('motivo', '  ')->call('devolver')->assertHasErrors(['motivo']);
    expect(Devolucion::count())->toBe(0);
});

it('solo se devuelve a participantes', function () {
    Livewire::actingAs($this->tienda)->test(Devolver::class)->call('elegir', $this->e ?? User::factory()->rol(Rol::Dirigente)->create()->id)->assertNotFound();
});

// ── Historial del puesto y de la persona ────────────────────────────────────

it('el historial del puesto suma lo cobrado y lista cobros y devoluciones propios', function () {
    gastarJlis($this, 30);
    app(CrearCobro::class)($this->tienda, 99); // sin pagar
    $otra = usuarioTienda();
    app(PagarCobro::class)(conJlis(1000), app(CrearCobro::class)($otra, 777)->token);

    $this->actingAs($this->tienda)->get(route('tienda.historial'))->assertOk()
        ->assertSee('30')->assertSee('Ana Benítez')->assertSee(__('jota.tienda.estado_pendiente'))->assertDontSee('777');
});

it('el historial de la persona junta puntos y LisCoins, y filtra', function () {
    darPuntos($this->yo, 20);
    gastarJlis($this, 30);

    $this->actingAs($this->yo)->get(route('app.historial'))->assertOk()
        ->assertSee(__('jota.historial.origen.tienda'))->assertSee('−30', false)
        ->assertSee(__('jota.historial.origen.ajuste'));

    $this->get(route('app.historial', ['ver' => 'puntos']))->assertDontSee(__('jota.historial.origen.tienda'));
});

it('el historial de la persona es solo de ella', function () {
    $otra = conJlis(50);
    $otra->update(['name' => 'Otra Persona']);
    gastarJlis($this, 10);

    $html = $this->actingAs($otra)->get(route('app.historial'))->getContent();

    expect($html)->not->toContain('Ana Benítez');
});

it('las pantallas de la tienda no son del participante ni del dirigente', function (string $ruta) {
    $this->actingAs($this->yo)->get(route($ruta))->assertForbidden();
    $this->actingAs(User::factory()->rol(Rol::Dirigente)->create())->get(route($ruta))->assertForbidden();
})->with(['tienda.inicio', 'tienda.devolver', 'tienda.historial']);

it('el administrador usa la tienda: cobra, devuelve y ve su historial', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();
    gastarJlis($this, 40);

    foreach (['tienda.inicio', 'tienda.devolver', 'tienda.historial'] as $ruta) {
        $this->actingAs($admin)->get(route($ruta))->assertOk();
    }

    Livewire::actingAs($admin)->test(Cobrar::class)->set('monto', '20')->call('generar')->assertSee(__('jota.escanear.qr_alt'));
    expect(Cobro::where('creado_por', $admin->id)->sole()->monto)->toBe(20);

    Livewire::actingAs($admin)->test(Devolver::class)->call('elegir', $this->yo->id)
        ->set('monto', '10')->set('motivo', 'Ajuste del admin')->call('devolver')->assertHasNoErrors();
    expect(Devolucion::sole()->registrado_por)->toBe($admin->id);

    // la tienda no ve los cobros del admin en su historial
    $this->actingAs($this->tienda)->get(route('tienda.historial'))->assertOk()->assertDontSee('Ajuste del admin');
});
