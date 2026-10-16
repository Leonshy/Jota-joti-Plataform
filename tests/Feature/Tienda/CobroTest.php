<?php

use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Puntos;
use App\Domain\Ledger\Reconciliador;
use App\Domain\Ledger\SaldoInsuficiente;
use App\Domain\Tienda\CobroNoExiste;
use App\Domain\Tienda\CobroVencido;
use App\Domain\Tienda\CobroYaPagado;
use App\Domain\Tienda\CrearCobro;
use App\Domain\Tienda\PagarCobro;
use App\Domain\Tienda\QrCobro;
use App\Enums\Rol;
use App\Models\Cobro;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\MovPuntoPatrulla;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    // MySQL guarda los vencimientos sin fracción de segundo: se fija el reloj al inicio de un segundo.
    $this->travelTo(now()->startOfSecond());
    $this->tienda = usuarioTienda();
    $this->crear = app(CrearCobro::class);
    $this->pagar = app(PagarCobro::class);
});

// ── Crear el cobro ──────────────────────────────────────────────────────────

it('crea un cobro con token aleatorio de un solo uso que vence a los 2 minutos', function () {
    $a = ($this->crear)($this->tienda, 30);
    $b = ($this->crear)($this->tienda, 30);

    expect($a->token)->toMatch('/^[a-f0-9]{64}$/')->and($b->token)->not->toBe($a->token)
        ->and($a->monto)->toBe(30)
        ->and($a->creado_por)->toBe($this->tienda->id)
        ->and($a->vence_at->equalTo(now()->addSeconds(120)))->toBeTrue()
        ->and($a->estado())->toBe(Cobro::PENDIENTE)
        ->and($a->toArray())->not->toHaveKey('token'); // no se serializa por accidente
});

it('el monto tiene que ser un entero positivo y razonable', function (int $monto) {
    ($this->crear)($this->tienda, $monto);
})->with([0, -5, 1_000_001])->throws(InvalidArgumentException::class);

it('solo la tienda y el administrador generan cobros', function (Rol $rol) {
    ($this->crear)(User::factory()->rol($rol)->create(), 10);
})->with([Rol::Beneficiario, Rol::Dirigente])->throws(AuthorizationException::class);

it('el administrador también genera cobros, y su historial es el suyo', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();

    expect(($this->crear)($admin, 10)->creado_por)->toBe($admin->id);
});

it('el cobro figura vencido pasados los 2 minutos', function () {
    $cobro = ($this->crear)($this->tienda, 10);

    $this->travel(119)->seconds();
    expect($cobro->fresh()->estado())->toBe(Cobro::PENDIENTE)->and($cobro->fresh()->segundosRestantes())->toBe(1);

    $this->travel(2)->seconds();
    expect($cobro->fresh()->estado())->toBe(Cobro::VENCIDO)->and($cobro->fresh()->segundosRestantes())->toBe(0);
});

it('el QR codifica la URL de la app con el token del cobro', function () {
    $cobro = ($this->crear)($this->tienda, 10);
    $qr = app(QrCobro::class);

    expect($qr->url($cobro))->toBe(url("/cobros/{$cobro->token}"))
        ->and($qr->svg($cobro))->toContain('<svg');
});

// ── Pagar ───────────────────────────────────────────────────────────────────

it('descuenta los LisCoins, deja el movimiento y marca el cobro como pagado', function () {
    $yo = conJlis(100);
    $cobro = ($this->crear)($this->tienda, 30);

    $pagado = ($this->pagar)($yo, $cobro->token);

    $mov = MovJli::where('cantidad', -30)->first();
    expect($yo->fresh()->jlis)->toBe(70)
        ->and($pagado->estado())->toBe(Cobro::PAGADO)
        ->and($pagado->pagado_por)->toBe($yo->id)
        ->and($pagado->pagado_at)->not->toBeNull()
        ->and($mov->user_id)->toBe($yo->id)
        ->and($mov->origen_id)->toBe($cobro->id)
        ->and($mov->motivo)->toBe('Compra en la tienda')
        ->and($mov->registrado_por)->toBe($yo->id);
});

it('se puede gastar exactamente todo el saldo y queda en cero', function () {
    $yo = conJlis(30);

    ($this->pagar)($yo, ($this->crear)($this->tienda, 30)->token);

    expect($yo->fresh()->jlis)->toBe(0);
});

it('saldo insuficiente: se rechaza, no se descuenta nada y el cobro sigue sin pagar', function () {
    $yo = conJlis(20);
    $cobro = ($this->crear)($this->tienda, 30);

    try {
        ($this->pagar)($yo, $cobro->token);
        $this->fail('Debió rechazar');
    } catch (SaldoInsuficiente $e) {
        expect($e->saldo)->toBe(20)->and($e->monto)->toBe(30);
    }

    expect($yo->fresh()->jlis)->toBe(20)
        ->and(MovJli::count())->toBe(1) // solo la carga inicial
        ->and($cobro->fresh()->estado())->toBe(Cobro::PENDIENTE)
        ->and($cobro->fresh()->pagado_por)->toBeNull();
});

it('tras un saldo insuficiente, si llegan LisCoins antes de que venza, se puede pagar', function () {
    $yo = conJlis(20);
    $cobro = ($this->crear)($this->tienda, 30);
    expect(fn () => ($this->pagar)($yo, $cobro->token))->toThrow(SaldoInsuficiente::class);

    app(Jlis::class)->acreditar($yo, 15, motivo: 'Misión aprobada');
    ($this->pagar)($yo, $cobro->token);

    expect($yo->fresh()->jlis)->toBe(5);
});

it('el saldo nunca queda negativo, ni con saldo cero', function () {
    $yo = conJlis(0);

    expect(fn () => ($this->pagar)($yo, ($this->crear)($this->tienda, 1)->token))->toThrow(SaldoInsuficiente::class)
        ->and($yo->fresh()->jlis)->toBe(0);
});

it('doble pago del mismo cobro: el segundo responde "ya pagado" y no descuenta de nuevo', function () {
    $yo = conJlis(100);
    $cobro = ($this->crear)($this->tienda, 30);
    ($this->pagar)($yo, $cobro->token);

    expect(fn () => ($this->pagar)($yo, $cobro->token))->toThrow(CobroYaPagado::class)
        ->and($yo->fresh()->jlis)->toBe(70)
        ->and(MovJli::where('cantidad', -30)->count())->toBe(1);
});

it('un cobro pagado por una persona no lo puede pagar otra', function () {
    $a = conJlis(100);
    $b = conJlis(100);
    $cobro = ($this->crear)($this->tienda, 30);
    ($this->pagar)($a, $cobro->token);

    expect(fn () => ($this->pagar)($b, $cobro->token))->toThrow(CobroYaPagado::class)
        ->and($b->fresh()->jlis)->toBe(100)
        ->and($cobro->fresh()->pagado_por)->toBe($a->id);
});

it('un cobro vencido no se paga y no descuenta', function () {
    $yo = conJlis(100);
    $cobro = ($this->crear)($this->tienda, 30);
    $this->travel(121)->seconds();

    expect(fn () => ($this->pagar)($yo, $cobro->token))->toThrow(CobroVencido::class)
        ->and($yo->fresh()->jlis)->toBe(100)
        ->and($cobro->fresh()->pagado_at)->toBeNull();
});

it('el cobro vence usando la hora del servidor, en el segundo exacto', function () {
    $yo = conJlis(100);
    $cobro = ($this->crear)($this->tienda, 30);

    $this->travelTo($cobro->vence_at->copy()->subSecond());
    ($this->pagar)($yo, $cobro->token);

    $otro = ($this->crear)($this->tienda, 30);
    $this->travelTo($otro->vence_at);

    expect(fn () => ($this->pagar)($yo, $otro->token))->toThrow(CobroVencido::class);
});

it('un token inexistente o inventado no existe', function () {
    ($this->pagar)(conJlis(100), str_repeat('a', 64));
})->throws(CobroNoExiste::class);

it('solo los beneficiarios pagan', function (Rol $rol) {
    $cobro = ($this->crear)($this->tienda, 10);

    ($this->pagar)(User::factory()->rol($rol)->create(), $cobro->token);
})->with([Rol::Tienda, Rol::Dirigente, Rol::Admin])->throws(AuthorizationException::class);

it('el pago no toca los puntos, ni individuales ni de patrulla', function () {
    $e = escenario(3);
    $yo = $e->miembros[0];
    app(Puntos::class)->individual($yo, 40);
    app(Puntos::class)->patrulla($e->patrulla, 60);
    conJlis(100, $yo);

    ($this->pagar)($yo, ($this->crear)($this->tienda, 30)->token);

    expect($yo->fresh()->puntos)->toBe(40)
        ->and($e->patrulla->fresh()->puntos)->toBe(60)
        ->and(MovPuntoIndividual::count())->toBe(1)
        ->and(MovPuntoPatrulla::count())->toBe(1);
});

it('la reconciliación queda limpia después de pagos', function () {
    $yo = conJlis(100);
    ($this->pagar)($yo, ($this->crear)($this->tienda, 30)->token);
    ($this->pagar)($yo, ($this->crear)($this->tienda, 45)->token);

    expect(app(Reconciliador::class)->diferencias())->toBe([])->and($yo->fresh()->jlis)->toBe(25);
});

// ── HTTP: lo que abre el QR ─────────────────────────────────────────────────

it('el QR abre el cobro y muestra el monto, la tienda y si alcanza el saldo', function () {
    $yo = conJlis(20);
    $cobro = ($this->crear)($this->tienda, 30);

    $this->actingAs($yo)->getJson(route('cobro.ver', $cobro->token))
        ->assertOk()
        ->assertJson(['estado' => 'pendiente', 'monto' => 30, 'tienda' => $this->tienda->name, 'saldo' => 20, 'alcanza' => false])
        ->assertJsonPath('segundos_restantes', 120)
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('el pago por HTTP responde ok, ya pagado, vencido y saldo insuficiente', function () {
    $yo = conJlis(50);
    $cobro = ($this->crear)($this->tienda, 30);

    $this->actingAs($yo)->postJson(route('cobro.pagar', $cobro->token))
        ->assertOk()->assertJson(['estado' => 'pagado', 'monto' => 30, 'saldo' => 20]);

    $this->actingAs($yo)->postJson(route('cobro.pagar', $cobro->token))
        ->assertOk()->assertJson(['estado' => 'ya_pagado']);

    $caro = ($this->crear)($this->tienda, 90);
    $this->actingAs($yo)->postJson(route('cobro.pagar', $caro->token))
        ->assertStatus(422)->assertJson(['estado' => 'saldo_insuficiente', 'saldo' => 20, 'monto' => 90, 'faltan' => 70]);

    $viejo = ($this->crear)($this->tienda, 5);
    $this->travel(121)->seconds();
    $this->actingAs($yo)->postJson(route('cobro.pagar', $viejo->token))
        ->assertStatus(422)->assertJson(['estado' => 'vencido']);
    $this->actingAs($yo)->getJson(route('cobro.ver', $viejo->token))->assertJson(['estado' => 'vencido']);

    expect($yo->fresh()->jlis)->toBe(20);
});

it('un token que no existe da 404 y sin sesión se pide ingresar', function () {
    $this->actingAs(conJlis(10))->getJson(route('cobro.ver', 'nada'))->assertNotFound()->assertJson(['estado' => 'no_existe']);
    $this->actingAs(conJlis(10))->postJson(route('cobro.pagar', 'nada'))->assertNotFound();
});

it('sin sesión iniciada se pide ingresar, tanto para ver como para pagar', function () {
    $cobro = ($this->crear)($this->tienda, 5);

    $this->get(route('cobro.ver', $cobro->token))->assertRedirect(route('ingresar'));
    $this->post(route('cobro.pagar', $cobro->token))->assertRedirect(route('ingresar'));
    expect($cobro->fresh()->estado())->toBe(Cobro::PENDIENTE);
});

it('la tienda no puede pagar un cobro desde su propia cuenta (403)', function () {
    $cobro = ($this->crear)($this->tienda, 5);

    $this->actingAs($this->tienda)->postJson(route('cobro.pagar', $cobro->token))->assertForbidden();
    $this->actingAs($this->tienda)->getJson(route('cobro.ver', $cobro->token))->assertForbidden();
});
