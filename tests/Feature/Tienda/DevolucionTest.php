<?php

use App\Domain\Ledger\Puntos;
use App\Domain\Ledger\Reconciliador;
use App\Domain\Tienda\BuscarParticipante;
use App\Domain\Tienda\CrearCobro;
use App\Domain\Tienda\DevolucionExcedeCompras;
use App\Domain\Tienda\Devolver;
use App\Domain\Tienda\PagarCobro;
use App\Enums\Rol;
use App\Models\Devolucion;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->tienda = usuarioTienda();
    $this->devolver = app(Devolver::class);
});

/** El participante compra por $monto en la tienda. */
function gastar(User $user, User $tienda, int $monto): void
{
    $cobro = app(CrearCobro::class)($tienda, $monto);
    app(PagarCobro::class)($user, $cobro->token);
}

// ── Devolver ────────────────────────────────────────────────────────────────

it('acredita los LisCoins, registra la devolución con motivo y quién la hizo', function () {
    $yo = conJlis(100);
    gastar($yo, $this->tienda, 40);

    $dev = ($this->devolver)($this->tienda, $yo, 25, '  Producto en mal estado  ');

    $mov = MovJli::where('cantidad', 25)->latest('id')->first();
    expect($yo->fresh()->jlis)->toBe(85)
        ->and($dev->motivo)->toBe('Producto en mal estado')
        ->and($dev->registrado_por)->toBe($this->tienda->id)
        ->and($dev->user_id)->toBe($yo->id)
        ->and($mov->origen_id)->toBe($dev->id)
        ->and($mov->registrado_por)->toBe($this->tienda->id)
        ->and($mov->motivo)->toContain('Producto en mal estado');
});

it('la devolución no toca los puntos', function () {
    $e = escenario(3);
    $yo = conJlis(100, $e->miembros[0]);
    app(Puntos::class)->individual($yo, 40);
    gastar($yo, $this->tienda, 30);

    ($this->devolver)($this->tienda, $yo, 30, 'Error de cobro');

    expect($yo->fresh()->puntos)->toBe(40)
        ->and($e->patrulla->fresh()->puntos)->toBe(0)
        ->and(MovPuntoIndividual::count())->toBe(1);
});

it('el motivo es obligatorio', function (string $motivo) {
    $yo = conJlis(100);
    gastar($yo, $this->tienda, 40);

    ($this->devolver)($this->tienda, $yo, 10, $motivo);
})->with(['', '   ', "\n"])->throws(InvalidArgumentException::class);

it('el monto tiene que ser positivo', function (int $monto) {
    $yo = conJlis(100);
    gastar($yo, $this->tienda, 40);

    ($this->devolver)($this->tienda, $yo, $monto, 'x');
})->with([0, -10])->throws(InvalidArgumentException::class);

it('no se devuelve más de lo que la persona gastó en la tienda', function () {
    $yo = conJlis(100);
    gastar($yo, $this->tienda, 40);

    try {
        ($this->devolver)($this->tienda, $yo, 41, 'de más');
        $this->fail('Debió rechazar');
    } catch (DevolucionExcedeCompras $e) {
        expect($e->maximo)->toBe(40);
    }

    expect($yo->fresh()->jlis)->toBe(60)->and(Devolucion::count())->toBe(0);
});

it('las devoluciones parciales se descuentan del tope', function () {
    $yo = conJlis(100);
    gastar($yo, $this->tienda, 40);

    ($this->devolver)($this->tienda, $yo, 15, 'primera');
    ($this->devolver)($this->tienda, $yo, 25, 'segunda');

    expect(fn () => ($this->devolver)($this->tienda, $yo, 1, 'tercera'))->toThrow(DevolucionExcedeCompras::class)
        ->and($yo->fresh()->jlis)->toBe(100);
});

it('quien no gastó nada no tiene nada para que le devuelvan', function () {
    ($this->devolver)($this->tienda, conJlis(100), 5, 'no compró');
})->throws(DevolucionExcedeCompras::class);

it('lo gastado por otra persona no cuenta para el tope', function () {
    $a = conJlis(100);
    $b = conJlis(100);
    gastar($a, $this->tienda, 40);

    ($this->devolver)($this->tienda, $b, 5, 'robo');
})->throws(DevolucionExcedeCompras::class);

it('un cobro vencido que no se pagó no habilita devolución', function () {
    $yo = conJlis(100);
    app(CrearCobro::class)($this->tienda, 40);

    ($this->devolver)($this->tienda, $yo, 5, 'x');
})->throws(DevolucionExcedeCompras::class);

it('solo la tienda y el administrador devuelven, y solo a participantes', function () {
    $yo = conJlis(100);
    gastar($yo, $this->tienda, 40);

    // el administrador también puede devolver
    expect(($this->devolver)(User::factory()->rol(Rol::Admin)->create(), $yo, 5, 'ajuste')->monto)->toBe(5);

    foreach ([Rol::Beneficiario, Rol::Dirigente] as $rol) {
        expect(fn () => ($this->devolver)(User::factory()->rol($rol)->create(), $yo, 5, 'x'))
            ->toThrow(AuthorizationException::class);
    }

    $dirigente = User::factory()->rol(Rol::Dirigente)->create();
    expect(fn () => ($this->devolver)($this->tienda, $dirigente, 5, 'x'))->toThrow(InvalidArgumentException::class);
});

it('la reconciliación queda limpia después de devoluciones', function () {
    $yo = conJlis(100);
    gastar($yo, $this->tienda, 40);
    ($this->devolver)($this->tienda, $yo, 40, 'todo');

    expect(app(Reconciliador::class)->diferencias())->toBe([])->and($yo->fresh()->jlis)->toBe(100);
});

// ── Buscar por CI o nombre ──────────────────────────────────────────────────

it('encuentra por CI exacto y por parte del nombre sin importar mayúsculas', function () {
    $ana = User::factory()->create(['ci' => '1234567', 'name' => 'Ana Benítez']);
    User::factory()->create(['ci' => '7654321', 'name' => 'Luis Gómez']);
    $buscar = app(BuscarParticipante::class);

    expect($buscar($this->tienda, '1234567')->pluck('id')->all())->toBe([$ana->id])
        ->and($buscar($this->tienda, 'beNÍt')->pluck('id')->all())->toBe([$ana->id])
        ->and($buscar($this->tienda, 'ana')->pluck('id')->all())->toBe([$ana->id])
        ->and($buscar($this->tienda, '123456')->count())->toBe(0); // un CI incompleto no es búsqueda por nombre
});

it('no busca por nombre con menos de 3 letras ni con la consulta vacía', function () {
    User::factory()->count(3)->create();
    $buscar = app(BuscarParticipante::class);

    expect($buscar($this->tienda, 'an')->count())->toBe(0)
        ->and($buscar($this->tienda, '')->count())->toBe(0)
        ->and($buscar($this->tienda, '   ')->count())->toBe(0);
});

it('solo devuelve beneficiarios y no expone datos de más', function () {
    User::factory()->rol(Rol::Dirigente)->create(['name' => 'Dirigente Pérez']);
    $ana = User::factory()->create(['name' => 'Pérez Ana']);

    $res = app(BuscarParticipante::class)($this->tienda, 'Pérez');

    expect($res->pluck('id')->all())->toBe([$ana->id])
        ->and(array_keys($res->first()->getAttributes()))->toBe(['id', 'ci', 'name', 'unidad_id', 'patrulla_id']);
});

it('los comodines de SQL se toman literales: "%%%" no lista a todos', function () {
    User::factory()->count(5)->create();

    expect(app(BuscarParticipante::class)($this->tienda, '%%%')->count())->toBe(0)
        ->and(app(BuscarParticipante::class)($this->tienda, '___')->count())->toBe(0);
});

it('limita la cantidad de resultados', function () {
    User::factory()->count(15)->create(['name' => 'Scout Prueba']);

    expect(app(BuscarParticipante::class)($this->tienda, 'Scout')->count())->toBe(10);
});

it('solo la tienda y el admin buscan', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();
    User::factory()->create(['name' => 'Scout Prueba']);

    expect(app(BuscarParticipante::class)($admin, 'Scout')->count())->toBe(1);

    foreach ([Rol::Beneficiario, Rol::Dirigente] as $rol) {
        expect(fn () => app(BuscarParticipante::class)(User::factory()->rol($rol)->create(), 'Scout'))
            ->toThrow(AuthorizationException::class);
    }
});
