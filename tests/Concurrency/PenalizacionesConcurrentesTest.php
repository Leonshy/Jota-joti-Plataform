<?php

use App\Domain\Ledger\Reconciliador;
use App\Domain\Penalizaciones\Penalizar;
use App\Models\MovPuntoIndividual;
use App\Models\Penalizacion;
use Illuminate\Support\Facades\Concurrency;

it('8 penalizaciones simultáneas a la misma persona se aplican todas y el saldo es exacto', function () {
    $e = escenario(1);
    $yo = darPuntos($e->miembros[0], 100);

    $r = array_count_values(Concurrency::driver('process')->run(
        array_map(fn (int $i) => tareaPenalizarPersona($e->dirigente->id, $yo->id, 5 * $i), range(1, 8)),
    ));

    // 5 + 10 + ... + 40 = 180
    expect($r['ok'])->toBe(8)
        ->and($yo->fresh()->puntos)->toBe(100 - 180)
        ->and(Penalizacion::count())->toBe(8)
        ->and((int) MovPuntoIndividual::where('user_id', $yo->id)->sum('cantidad'))->toBe(-80)
        ->and(app(Reconciliador::class)->diferencias())->toBe([]);
});

it('doble anulación simultánea: se devuelve una sola vez', function () {
    $e = escenario(1);
    $yo = $e->miembros[0];
    $p = app(Penalizar::class)->persona($e->dirigente, $yo, 30, 'x');

    $r = array_count_values(Concurrency::driver('process')->run(
        array_map(fn () => tareaAnularPenalizacion($e->dirigente->id, $p->id), range(1, 6)),
    ));

    expect($r['ok'])->toBe(1)->and($r['ya anulada'])->toBe(5)
        ->and($yo->fresh()->puntos)->toBe(0)
        ->and(MovPuntoIndividual::where('origen_id', $p->id)->count())->toBe(2);
});

it('penalizar y anular a la vez a la misma persona no se traba ni pierde puntos', function () {
    $e = escenario(1);
    $yo = darPuntos($e->miembros[0], 100);
    $vieja = app(Penalizar::class)->persona($e->dirigente, $yo, 10, 'x');

    $tareas = [
        tareaAnularPenalizacion($e->dirigente->id, $vieja->id),
        tareaPenalizarPersona($e->dirigente->id, $yo->id, 7),
        tareaPenalizarPersona($e->dirigente->id, $yo->id, 3),
        tareaPenalizarPersona($e->dirigente->id, $yo->id, 5),
    ];
    Concurrency::driver('process')->run($tareas);

    // 100 − 10 (vieja) + 10 (anulada) − 7 − 3 − 5 = 85
    expect($yo->fresh()->puntos)->toBe(85)->and(app(Reconciliador::class)->diferencias())->toBe([]);
});
