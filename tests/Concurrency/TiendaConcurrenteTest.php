<?php

use App\Domain\Ledger\Reconciliador;
use App\Domain\Tienda\CrearCobro;
use App\Domain\Tienda\PagarCobro;
use App\Models\Cobro;
use App\Models\Devolucion;
use App\Models\MovJli;
use Illuminate\Support\Facades\Concurrency;

function pagosConcurrentes(array $tareas): array
{
    return array_count_values(Concurrency::driver('process')->run($tareas));
}

it('doble pago simultáneo del mismo cobro por la misma persona: se descuenta una sola vez', function () {
    $tienda = usuarioTienda();
    $yo = conJlis(100);
    $cobro = app(CrearCobro::class)($tienda, 30);

    $r = pagosConcurrentes(array_map(fn () => tareaPagarCobro($yo->id, $cobro->token), range(1, 8)));

    expect($r['ok'])->toBe(1)->and($r['ya pagado'])->toBe(7)
        ->and($yo->fresh()->jlis)->toBe(70)
        ->and(MovJli::where('cantidad', -30)->count())->toBe(1)
        ->and($cobro->fresh()->pagado_por)->toBe($yo->id);
});

it('el mismo cobro pagado a la vez por 6 personas distintas: paga una sola', function () {
    $tienda = usuarioTienda();
    $pagadores = collect(range(1, 6))->map(fn () => conJlis(100));
    $cobro = app(CrearCobro::class)($tienda, 30);

    $r = pagosConcurrentes($pagadores->map(fn ($u) => tareaPagarCobro($u->id, $cobro->token))->all());

    expect($r['ok'])->toBe(1)->and($r['ya pagado'])->toBe(5)
        ->and($pagadores->sum(fn ($u) => $u->fresh()->jlis))->toBe(6 * 100 - 30)
        ->and(MovJli::where('cantidad', -30)->count())->toBe(1);
});

it('una persona pagando 6 cobros distintos a la vez con 100 LisCoins: pasan 3 y nunca queda negativo', function () {
    $tienda = usuarioTienda();
    $yo = conJlis(100);
    $cobros = collect(range(1, 6))->map(fn () => app(CrearCobro::class)($tienda, 30));

    $r = pagosConcurrentes($cobros->map(fn ($c) => tareaPagarCobro($yo->id, $c->token))->all());

    expect($r['ok'])->toBe(3)->and($r['saldo insuficiente'])->toBe(3)
        ->and($yo->fresh()->jlis)->toBe(10)
        ->and((int) MovJli::where('user_id', $yo->id)->sum('cantidad'))->toBe(10)
        ->and(Cobro::whereNotNull('pagado_at')->count())->toBe(3)
        ->and(app(Reconciliador::class)->diferencias())->toBe([]);
});

it('muchos participantes pagando cobros distintos a la vez: cada uno paga el suyo', function () {
    $tienda = usuarioTienda();
    $pagadores = collect(range(1, 8))->map(fn () => conJlis(50));
    $cobros = $pagadores->map(fn () => app(CrearCobro::class)($tienda, 20));

    $r = pagosConcurrentes($pagadores->map(fn ($u, $i) => tareaPagarCobro($u->id, $cobros[$i]->token))->all());

    expect($r['ok'])->toBe(8)
        ->and($pagadores->every(fn ($u) => $u->fresh()->jlis === 30))->toBeTrue()
        ->and(Cobro::whereNotNull('pagado_at')->count())->toBe(8);
});

it('devoluciones simultáneas no superan el tope de lo gastado', function () {
    $tienda = usuarioTienda();
    $yo = conJlis(200);
    $gasto = app(CrearCobro::class)($tienda, 100);
    app(PagarCobro::class)($yo, $gasto->token);

    // 6 devoluciones de 30 contra 100 gastados: pasan exactamente 3 (90), la cuarta excedería.
    $r = pagosConcurrentes(array_map(fn () => tareaDevolver($tienda->id, $yo->id, 30), range(1, 6)));

    expect($r['ok'])->toBe(3)->and($r['excede'])->toBe(3)
        ->and((int) Devolucion::sum('monto'))->toBe(90)
        ->and($yo->fresh()->jlis)->toBe(100 + 90) // 200 − 100 gastados + 90 devueltos
        ->and(app(Reconciliador::class)->diferencias())->toBe([]);
});
