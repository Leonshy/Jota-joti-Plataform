<?php

use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Reconciliador;
use App\Domain\Penalizaciones\AnularPenalizacion;
use App\Domain\Penalizaciones\PenalizacionYaAnulada;
use App\Domain\Penalizaciones\Penalizar;
use App\Domain\Ranking\RankingUnidad;
use App\Enums\Rol;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\MovPuntoPatrulla;
use App\Models\Penalizacion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->e = escenario(3);
    $this->yo = $this->e->miembros[0];
    $this->penalizar = app(Penalizar::class);
});

// ── A una persona ───────────────────────────────────────────────────────────

it('resta puntos a la persona con motivo y deja quién la aplicó', function () {
    darPuntos($this->yo, 50);

    $p = ($this->penalizar)->persona($this->e->dirigente, $this->yo, 15, '  Llegó tarde al formar  ');

    $mov = MovPuntoIndividual::where('cantidad', -15)->first();
    expect($this->yo->fresh()->puntos)->toBe(35)
        ->and($p->user_id)->toBe($this->yo->id)->and($p->patrulla_id)->toBeNull()
        ->and($p->cantidad)->toBe(15)
        ->and($p->motivo)->toBe('Llegó tarde al formar')
        ->and($p->registrado_por)->toBe($this->e->dirigente->id)
        ->and($mov->registrado_por)->toBe($this->e->dirigente->id)
        ->and($mov->origen_id)->toBe($p->id)
        ->and($mov->motivo)->toContain('Llegó tarde al formar');
});

it('los puntos pueden quedar negativos (D14)', function () {
    ($this->penalizar)->persona($this->e->dirigente, $this->yo, 30, 'Falta grave');

    expect($this->yo->fresh()->puntos)->toBe(-30);
});

it('la penalización no toca los LisCoins', function () {
    conJlis(80, $this->yo);

    ($this->penalizar)->persona($this->e->dirigente, $this->yo, 40, 'Falta');

    expect($this->yo->fresh()->jlis)->toBe(80)
        ->and(MovJli::count())->toBe(1) // solo la carga inicial
        ->and($this->yo->fresh()->puntos)->toBe(-40);
});

it('una persona penalizada puede seguir gastando sus LisCoins', function () {
    conJlis(50, $this->yo);
    ($this->penalizar)->persona($this->e->dirigente, $this->yo, 500, 'Falta');

    app(Jlis::class)->debitar($this->yo, 50);

    expect($this->yo->fresh()->jlis)->toBe(0);
});

// ── A una patrulla ──────────────────────────────────────────────────────────

it('resta puntos al registro de la patrulla y no toca a las personas ni los LisCoins', function () {
    darPuntosPatrulla($this->e->patrulla, 100);
    foreach ($this->e->miembros as $m) {
        conJlis(20, $m);
    }

    $p = ($this->penalizar)->patrulla($this->e->dirigente, $this->e->patrulla, 30, 'Dejaron el fogón encendido');

    expect($this->e->patrulla->fresh()->puntos)->toBe(70)
        ->and($p->patrulla_id)->toBe($this->e->patrulla->id)->and($p->user_id)->toBeNull()
        ->and(MovPuntoPatrulla::where('cantidad', -30)->count())->toBe(1)
        ->and(MovPuntoIndividual::count())->toBe(0);

    foreach ($this->e->miembros as $m) {
        expect($m->fresh()->jlis)->toBe(20)->and($m->fresh()->puntos)->toBe(0);
    }
});

it('la penalización baja el puntaje del equipo en el ranking', function () {
    $r = rankingEquipo();
    $admin = User::factory()->rol(Rol::Admin)->create();

    ($this->penalizar)->patrulla($admin, $r->pumas, 100, 'Falta');
    $completo = app(RankingUnidad::class)->completo($admin, $r->unidad);

    expect(array_column($completo, 'patrulla'))->toBe(['Águilas', 'Pumas', 'Zorros'])
        ->and(array_column($completo, 'puntaje'))->toBe([130, 40, 0]);
});

// ── Validaciones ────────────────────────────────────────────────────────────

it('el motivo es obligatorio', function (string $motivo) {
    expect(fn () => ($this->penalizar)->persona($this->e->dirigente, $this->yo, 5, $motivo))->toThrow(InvalidArgumentException::class)
        ->and(fn () => ($this->penalizar)->patrulla($this->e->dirigente, $this->e->patrulla, 5, $motivo))->toThrow(InvalidArgumentException::class)
        ->and(Penalizacion::count())->toBe(0)
        ->and(MovPuntoIndividual::count())->toBe(0);
})->with(['', '   ', "\n\t"]);

it('la cantidad tiene que ser positiva y razonable', function (int $cantidad) {
    ($this->penalizar)->persona($this->e->dirigente, $this->yo, $cantidad, 'x');
})->with([0, -5, 1_000_001])->throws(InvalidArgumentException::class);

it('solo se penaliza a participantes', function () {
    ($this->penalizar)->persona($this->e->dirigente, User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => $this->e->unidad->id]), 5, 'x');
})->throws(InvalidArgumentException::class);

// ── Quién puede ─────────────────────────────────────────────────────────────

it('el dirigente de otra unidad recibe 403 y no se resta nada', function () {
    $ajeno = escenario();

    expect(fn () => ($this->penalizar)->persona($ajeno->dirigente, $this->yo, 5, 'x'))->toThrow(AuthorizationException::class)
        ->and(fn () => ($this->penalizar)->patrulla($ajeno->dirigente, $this->e->patrulla, 5, 'x'))->toThrow(AuthorizationException::class)
        ->and(Penalizacion::count())->toBe(0)
        ->and($this->yo->fresh()->puntos)->toBe(0);
});

it('el admin penaliza en cualquier unidad', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();

    ($this->penalizar)->persona($admin, $this->yo, 5, 'x');
    ($this->penalizar)->patrulla($admin, $this->e->patrulla, 5, 'x');

    expect(Penalizacion::count())->toBe(2);
});

it('un beneficiario o la tienda no penalizan', function (Rol $rol) {
    $quien = User::factory()->rol($rol)->create(['unidad_id' => $this->e->unidad->id]);

    ($this->penalizar)->persona($quien, $this->e->miembros[1], 5, 'x');
})->with([Rol::Beneficiario, Rol::Tienda])->throws(AuthorizationException::class);

it('la base exige que la penalización sea a una persona o a una patrulla, nunca a ambas ni a ninguna', function () {
    $datos = ['cantidad' => 5, 'motivo' => 'x', 'registrado_por' => $this->e->dirigente->id];

    expect(fn () => Penalizacion::create($datos))->toThrow(QueryException::class)
        ->and(fn () => Penalizacion::create($datos + ['user_id' => $this->yo->id, 'patrulla_id' => $this->e->patrulla->id]))->toThrow(QueryException::class);
});

// ── Anular ──────────────────────────────────────────────────────────────────

it('anular suma un movimiento de signo contrario y deja la marca; el original no se toca', function () {
    darPuntos($this->yo, 50);
    $p = ($this->penalizar)->persona($this->e->dirigente, $this->yo, 20, 'Error de dedo');

    app(AnularPenalizacion::class)($this->e->dirigente, $p, 'Se penalizó a la persona equivocada');

    $p->refresh();
    expect($this->yo->fresh()->puntos)->toBe(50)
        ->and($p->estaAnulada())->toBeTrue()
        ->and($p->anulada_por)->toBe($this->e->dirigente->id)
        ->and($p->motivo_anulacion)->toBe('Se penalizó a la persona equivocada')
        ->and(MovPuntoIndividual::where('origen_id', $p->id)->pluck('cantidad')->sort()->values()->all())->toBe([-20, 20])
        ->and($p->cantidad)->toBe(20);
});

it('anular una penalización de patrulla devuelve los puntos a la patrulla', function () {
    $p = ($this->penalizar)->patrulla($this->e->dirigente, $this->e->patrulla, 40, 'x');

    app(AnularPenalizacion::class)($this->e->dirigente, $p, 'Error');

    expect($this->e->patrulla->fresh()->puntos)->toBe(0)->and(MovPuntoPatrulla::count())->toBe(2);
});

it('una penalización se anula una sola vez', function () {
    $p = ($this->penalizar)->persona($this->e->dirigente, $this->yo, 20, 'x');
    app(AnularPenalizacion::class)($this->e->dirigente, $p, 'Error');

    expect(fn () => app(AnularPenalizacion::class)($this->e->dirigente, $p, 'Otra vez'))->toThrow(PenalizacionYaAnulada::class)
        ->and($this->yo->fresh()->puntos)->toBe(0);
});

it('anular exige motivo y permisos sobre la unidad', function () {
    $p = ($this->penalizar)->persona($this->e->dirigente, $this->yo, 20, 'x');

    expect(fn () => app(AnularPenalizacion::class)($this->e->dirigente, $p, '  '))->toThrow(InvalidArgumentException::class)
        ->and(fn () => app(AnularPenalizacion::class)(escenario()->dirigente, $p, 'No me toca'))->toThrow(AuthorizationException::class)
        ->and(fn () => app(AnularPenalizacion::class)($this->e->miembros[1], $p, 'Soy compañero'))->toThrow(AuthorizationException::class)
        ->and($this->yo->fresh()->puntos)->toBe(-20)
        ->and($p->fresh()->estaAnulada())->toBeFalse();
});

it('el admin puede anular una penalización de cualquier unidad', function () {
    $p = ($this->penalizar)->persona($this->e->dirigente, $this->yo, 20, 'x');

    app(AnularPenalizacion::class)(User::factory()->rol(Rol::Admin)->create(), $p, 'Revisión del admin');

    expect($this->yo->fresh()->puntos)->toBe(0);
});

it('la reconciliación queda limpia después de penalizar y anular', function () {
    $p = ($this->penalizar)->persona($this->e->dirigente, $this->yo, 20, 'x');
    ($this->penalizar)->patrulla($this->e->dirigente, $this->e->patrulla, 10, 'y');
    app(AnularPenalizacion::class)($this->e->dirigente, $p, 'Error');

    expect(app(Reconciliador::class)->diferencias())->toBe([]);
});
