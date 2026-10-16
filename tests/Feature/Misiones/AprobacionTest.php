<?php

use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Reconciliador;
use App\Domain\Misiones\AprobarEntrega;
use App\Domain\Misiones\EntregaYaRevisada;
use App\Enums\EstadoEntrega;
use App\Enums\Rol;
use App\Models\Entrega;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\MovPuntoPatrulla;
use App\Models\Patrulla;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;

function entregaPendiente($e, array $mision = [], ?string $estado = null, int $quien = 0): Entrega
{
    $mision = misionPara($e->unidad, $mision, $estado);
    $user = $e->miembros[$quien];

    return Entrega::factory()->create([
        'mision_id' => $mision->id,
        'user_id' => $user->id,
        'patrulla_id' => $mision->esPorPatrulla() ? $e->patrulla->id : null,
    ]);
}

it('misión individual: +P puntos y +P LisCoins a la persona, con quién registró', function () {
    $e = escenario();
    $entrega = entregaPendiente($e, ['puntos' => 40]);

    app(AprobarEntrega::class)($e->dirigente, $entrega);

    $yo = $e->miembros[0]->fresh();
    expect($yo->puntos)->toBe(40)
        ->and($yo->jlis)->toBe(40)
        ->and($e->patrulla->fresh()->puntos)->toBe(0)
        ->and($entrega->fresh()->estado)->toBe(EstadoEntrega::Aprobada)
        ->and($entrega->fresh()->revisado_por)->toBe($e->dirigente->id)
        ->and(MovPuntoIndividual::first()->registrado_por)->toBe($e->dirigente->id)
        ->and(MovPuntoIndividual::first()->origen_id)->toBe($entrega->id);
});

it('misión por patrulla: +P a la patrulla una vez y ⌈P/n⌉ LisCoins a cada integrante (50/3 = 17)', function () {
    $e = escenario(3);
    $entrega = entregaPendiente($e, ['puntos' => 50], 'porPatrulla');

    app(AprobarEntrega::class)($e->dirigente, $entrega);

    expect($e->patrulla->fresh()->puntos)->toBe(50)
        ->and(MovPuntoPatrulla::count())->toBe(1);

    foreach ($e->miembros as $miembro) {
        expect($miembro->fresh()->jlis)->toBe(17)
            ->and($miembro->fresh()->puntos)->toBe(0); // los puntos van a la patrulla, no a la persona
    }
    expect(MovJli::count())->toBe(3)->and(MovPuntoIndividual::count())->toBe(0);
});

it('el redondeo siempre es hacia arriba', function (int $integrantes, int $puntos, int $esperado) {
    $e = escenario($integrantes);
    $entrega = entregaPendiente($e, ['puntos' => $puntos], 'porPatrulla');

    app(AprobarEntrega::class)($e->dirigente, $entrega);

    expect($e->miembros[0]->fresh()->jlis)->toBe($esperado);
})->with([
    'exacto 60/3' => [3, 60, 20],
    '50/3' => [3, 50, 17],
    '50/7' => [7, 50, 8],
    '10/4' => [4, 10, 3],
    '1/5' => [5, 1, 1],
]);

it('reparte entre los integrantes al momento de acreditar, no entre los de antes', function () {
    $e = escenario(3);
    $entrega = entregaPendiente($e, ['puntos' => 60], 'porPatrulla');
    User::factory()->create(['unidad_id' => $e->unidad->id, 'patrulla_id' => $e->patrulla->id]); // llega un cuarto

    app(AprobarEntrega::class)($e->dirigente, $entrega);

    expect($e->miembros[0]->fresh()->jlis)->toBe(15); // 60/4
});

it('una patrulla sin integrantes igual suma los puntos y no falla', function () {
    $e = escenario(1);
    $entrega = entregaPendiente($e, [], 'porPatrulla');
    $vacia = Patrulla::factory()->create(['unidad_id' => $e->unidad->id]);
    $entrega->update(['patrulla_id' => $vacia->id]);

    app(AprobarEntrega::class)($e->dirigente, $entrega);

    expect($vacia->fresh()->puntos)->toBe(50)->and(MovJli::count())->toBe(0);
});

it('aprobar dos veces responde "ya revisada" y no acredita de nuevo', function () {
    $e = escenario();
    $entrega = entregaPendiente($e);
    $aprobar = app(AprobarEntrega::class);

    $aprobar($e->dirigente, $entrega);

    expect(fn () => $aprobar($e->dirigente, $entrega))->toThrow(EntregaYaRevisada::class)
        ->and($e->miembros[0]->fresh()->puntos)->toBe(50)
        ->and(MovPuntoIndividual::count())->toBe(1)
        ->and(MovJli::count())->toBe(1);
});

it('la base impide dos entregas aprobadas para el mismo sujeto y misión', function () {
    $e = escenario();
    $mision = misionPara($e->unidad);
    $datos = ['mision_id' => $mision->id, 'user_id' => $e->miembros[0]->id];

    Entrega::factory()->create($datos + ['estado' => EstadoEntrega::Aprobada]);

    expect(fn () => Entrega::factory()->create($datos + ['estado' => EstadoEntrega::Aprobada]))
        ->toThrow(UniqueConstraintViolationException::class);

    // Pendientes y rechazadas sí pueden repetirse.
    Entrega::factory()->create($datos + ['estado' => EstadoEntrega::Rechazada]);
    Entrega::factory()->create($datos + ['estado' => EstadoEntrega::Pendiente]);
    expect(Entrega::count())->toBe(3);
});

it('una entrega ya rechazada no se puede aprobar', function () {
    $e = escenario();
    $entrega = entregaPendiente($e);
    $entrega->update(['estado' => EstadoEntrega::Rechazada]);

    expect(fn () => app(AprobarEntrega::class)($e->dirigente, $entrega))->toThrow(EntregaYaRevisada::class)
        ->and(MovJli::count())->toBe(0);
});

it('el dirigente de otra unidad recibe 403 y no se acredita nada', function () {
    $e = escenario();
    $otra = escenario();
    $entrega = entregaPendiente($e);

    expect(fn () => app(AprobarEntrega::class)($otra->dirigente, $entrega))->toThrow(AuthorizationException::class)
        ->and($entrega->fresh()->estado)->toBe(EstadoEntrega::Pendiente)
        ->and(MovPuntoIndividual::count())->toBe(0)
        ->and(MovJli::count())->toBe(0);
});

it('el admin aprueba en cualquier unidad', function () {
    $e = escenario();
    $admin = User::factory()->rol(Rol::Admin)->create();

    app(AprobarEntrega::class)($admin, entregaPendiente($e));

    expect($e->miembros[0]->fresh()->puntos)->toBe(50);
});

it('un beneficiario, aunque sea de la misma unidad, no puede aprobar', function () {
    $e = escenario();

    app(AprobarEntrega::class)($e->miembros[1], entregaPendiente($e));
})->throws(AuthorizationException::class);

it('nadie se aprueba a sí mismo: un beneficiario que entrega no puede revisar', function () {
    $e = escenario();

    app(AprobarEntrega::class)($e->miembros[0], entregaPendiente($e));
})->throws(AuthorizationException::class);

it('después de aprobar, la reconciliación no encuentra diferencias', function () {
    $e = escenario(5);
    app(AprobarEntrega::class)($e->dirigente, entregaPendiente($e, ['puntos' => 50], 'porPatrulla'));
    app(AprobarEntrega::class)($e->dirigente, entregaPendiente($e, ['puntos' => 30], null, 1));

    expect(app(Reconciliador::class)->diferencias())->toBe([]);
});

it('los LisCoins de una aprobación quedan como saldo gastable independiente de los puntos', function () {
    $e = escenario();
    app(AprobarEntrega::class)($e->dirigente, entregaPendiente($e, ['puntos' => 20]));

    app(Jlis::class)->debitar($e->miembros[0], 15, motivo: 'Compra');

    expect($e->miembros[0]->fresh()->jlis)->toBe(5)->and($e->miembros[0]->fresh()->puntos)->toBe(20);
});
