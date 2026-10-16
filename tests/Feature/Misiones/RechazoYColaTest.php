<?php

use App\Domain\Misiones\AprobarEntrega;
use App\Domain\Misiones\CerrarMision;
use App\Domain\Misiones\ColaValidacion;
use App\Domain\Misiones\EntregaInvalida;
use App\Domain\Misiones\EntregaYaRevisada;
use App\Domain\Misiones\RechazarEntrega;
use App\Enums\EstadoEntrega;
use App\Enums\Rol;
use App\Models\Entrega;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

function pendienteDe($e, int $quien = 0, array $mision = []): Entrega
{
    $m = misionPara($e->unidad, $mision);

    return Entrega::factory()->create(['mision_id' => $m->id, 'user_id' => $e->miembros[$quien]->id]);
}

it('rechaza con motivo, deja quién revisó y no toca puntos ni LisCoins', function () {
    $e = escenario();
    $entrega = pendienteDe($e);

    app(RechazarEntrega::class)($e->dirigente, $entrega, '  La foto está borrosa  ');

    $entrega->refresh();
    expect($entrega->estado)->toBe(EstadoEntrega::Rechazada)
        ->and($entrega->motivo_rechazo)->toBe('La foto está borrosa')
        ->and($entrega->revisado_por)->toBe($e->dirigente->id)
        ->and($entrega->revisado_at)->not->toBeNull()
        ->and(MovPuntoIndividual::count())->toBe(0)
        ->and(MovJli::count())->toBe(0);
});

it('el motivo es obligatorio', function (string $motivo) {
    $e = escenario();
    $entrega = pendienteDe($e);

    expect(fn () => app(RechazarEntrega::class)($e->dirigente, $entrega, $motivo))->toThrow(EntregaInvalida::class)
        ->and($entrega->fresh()->estado)->toBe(EstadoEntrega::Pendiente);
})->with(['', '   ', "\n\t"]);

it('una entrega revisada no se puede rechazar de nuevo ni después de aprobada', function () {
    $e = escenario();
    $entrega = pendienteDe($e);
    app(AprobarEntrega::class)($e->dirigente, $entrega);

    app(RechazarEntrega::class)($e->dirigente, $entrega, 'tarde');
})->throws(EntregaYaRevisada::class);

it('el dirigente de otra unidad no puede rechazar (403)', function () {
    $e = escenario();
    $entrega = pendienteDe($e);

    expect(fn () => app(RechazarEntrega::class)(escenario()->dirigente, $entrega, 'no me toca'))
        ->toThrow(AuthorizationException::class)
        ->and($entrega->fresh()->estado)->toBe(EstadoEntrega::Pendiente);
});

it('la cola del dirigente muestra solo pendientes de su unidad, las más antiguas primero', function () {
    $e = escenario();
    $ajeno = escenario();

    $vieja = pendienteDe($e, 0);
    $this->travel(5)->minutes();
    $nueva = pendienteDe($e, 1);
    pendienteDe($ajeno);
    $aprobada = pendienteDe($e, 2);
    app(AprobarEntrega::class)($e->dirigente, $aprobada);

    $ids = app(ColaValidacion::class)($e->dirigente)->pluck('id')->all();

    expect($ids)->toBe([$vieja->id, $nueva->id]);
});

it('el admin ve la cola de todas las unidades y puede acotarla a una', function () {
    $e = escenario();
    $otra = escenario();
    $admin = User::factory()->rol(Rol::Admin)->create();
    pendienteDe($e);
    pendienteDe($otra);

    expect(app(ColaValidacion::class)($admin)->count())->toBe(2)
        ->and(app(ColaValidacion::class)($admin, $otra->unidad->id)->count())->toBe(1);
});

it('un dirigente no puede ampliar la cola pasando otra unidad', function () {
    $e = escenario();
    $otra = escenario();
    pendienteDe($otra);

    expect(app(ColaValidacion::class)($e->dirigente, $otra->unidad->id)->count())->toBe(0);
});

it('beneficiarios, tienda y dirigentes sin unidad no tienen cola', function () {
    $e = escenario();
    $tienda = User::factory()->rol(Rol::Tienda)->create();
    $sinUnidad = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => null]);

    foreach ([$e->miembros[0], $tienda, $sinUnidad] as $quien) {
        expect(fn () => app(ColaValidacion::class)($quien))->toThrow(AuthorizationException::class);
    }
});

it('la cola no hace consultas N+1: la cantidad de queries no crece con las entregas', function () {
    $consultas = function (int $entregas): int {
        $e = escenario($entregas);
        foreach (range(0, $entregas - 1) as $i) {
            pendienteDe($e, $i);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(ColaValidacion::class)($e->dirigente)->get()->each(function ($entrega) {
            $entrega->mision->titulo;
            $entrega->user->name;
            $entrega->patrulla?->nombre;
            $entrega->imagenes->count();
        });
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    expect($consultas(10))->toBe($consultas(3));
});

it('el admin cierra una misión a mano y deja de aceptar entregas', function () {
    $e = escenario();
    $admin = User::factory()->rol(Rol::Admin)->create();
    $mision = misionPara($e->unidad);

    app(CerrarMision::class)($admin, $mision);

    expect($mision->fresh()->cerrada_at)->not->toBeNull()
        ->and($mision->fresh()->estaAbierta())->toBeFalse();
});

it('el dirigente no puede cerrar misiones', function () {
    $e = escenario();

    app(CerrarMision::class)($e->dirigente, misionPara($e->unidad));
})->throws(AuthorizationException::class);
