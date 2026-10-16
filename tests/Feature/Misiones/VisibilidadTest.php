<?php

use App\Enums\Rol;
use App\Models\Mision;
use App\Models\Unidad;
use App\Models\User;

it('no muestra la misión antes de la hora de lanzamiento y la muestra después', function () {
    $e = escenario();
    misionPara($e->unidad, ['titulo' => 'Futura'], 'futura');
    $visible = misionPara($e->unidad, ['titulo' => 'Ya lanzada']);

    expect(Mision::visiblesPara($e->miembros[0])->pluck('id')->all())->toBe([$visible->id]);

    $this->travel(2)->hours();

    expect(Mision::visiblesPara($e->miembros[0])->count())->toBe(2);
});

it('no muestra misiones de otras unidades', function () {
    $e = escenario();
    $otra = Unidad::factory()->create();
    misionPara($otra);

    expect(Mision::visiblesPara($e->miembros[0])->count())->toBe(0);
});

it('una misión para varias unidades la ven todas ellas', function () {
    $e = escenario();
    $otra = escenario();
    $mision = misionPara($e->unidad);
    $mision->unidades()->attach($otra->unidad);

    expect(Mision::visiblesPara($e->miembros[0])->count())->toBe(1)
        ->and(Mision::visiblesPara($otra->miembros[0])->count())->toBe(1);
});

it('está abierta salvo que venza o la cierren', function () {
    $e = escenario();

    expect(misionPara($e->unidad)->estaAbierta())->toBeTrue()
        ->and(misionPara($e->unidad, [], 'vencida')->estaAbierta())->toBeFalse()
        ->and(misionPara($e->unidad, [], 'cerrada')->estaAbierta())->toBeFalse()
        ->and(misionPara($e->unidad, [], 'futura')->estaAbierta())->toBeFalse();
});

it('vence usando la hora del servidor', function () {
    $e = escenario();
    $mision = misionPara($e->unidad, ['vence_at' => now()->addMinutes(10)]);

    expect($mision->estaAbierta())->toBeTrue();

    $this->travel(11)->minutes();

    expect($mision->fresh()->estaAbierta())->toBeFalse();
});

it('la policy deja ver a la unidad destino y al admin, no a otras unidades', function () {
    $e = escenario();
    $ajeno = escenario();
    $admin = User::factory()->rol(Rol::Admin)->create();
    $mision = misionPara($e->unidad);

    expect($e->miembros[0]->can('ver', $mision))->toBeTrue()
        ->and($e->dirigente->can('ver', $mision))->toBeTrue()
        ->and($ajeno->miembros[0]->can('ver', $mision))->toBeFalse()
        ->and($ajeno->dirigente->can('ver', $mision))->toBeFalse()
        ->and($admin->can('ver', $mision))->toBeTrue();
});
