<?php

use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\MovPuntoPatrulla;
use Illuminate\Support\Facades\Concurrency;

it('doble aprobación simultánea de una misión individual: una sola acreditación', function () {
    $e = escenario();
    $mision = misionPara($e->unidad, ['puntos' => 40]);
    $entrega = Entrega::factory()->create(['mision_id' => $mision->id, 'user_id' => $e->miembros[0]->id]);

    $tareas = array_map(fn () => tareaAprobarEntrega($entrega->id, $e->dirigente->id), range(1, 8));
    $resultados = array_count_values(Concurrency::driver('process')->run($tareas));

    expect($resultados['ok'])->toBe(1)
        ->and($resultados['ya revisada'])->toBe(7)
        ->and($e->miembros[0]->fresh()->puntos)->toBe(40)
        ->and($e->miembros[0]->fresh()->jlis)->toBe(40)
        ->and(MovPuntoIndividual::count())->toBe(1)
        ->and(MovJli::count())->toBe(1)
        ->and($entrega->fresh()->estado)->toBe(EstadoEntrega::Aprobada);
});

it('doble aprobación simultánea de una misión por patrulla: puntos y LisCoins una sola vez', function () {
    $e = escenario(4);
    $mision = misionPara($e->unidad, ['puntos' => 50], 'porPatrulla');
    $entrega = Entrega::factory()->create([
        'mision_id' => $mision->id,
        'user_id' => $e->miembros[0]->id,
        'patrulla_id' => $e->patrulla->id,
    ]);

    $tareas = array_map(fn () => tareaAprobarEntrega($entrega->id, $e->dirigente->id), range(1, 8));
    $resultados = array_count_values(Concurrency::driver('process')->run($tareas));

    expect($resultados['ok'])->toBe(1)
        ->and($e->patrulla->fresh()->puntos)->toBe(50)
        ->and(MovPuntoPatrulla::count())->toBe(1)
        ->and(MovJli::count())->toBe(4);

    foreach ($e->miembros as $miembro) {
        expect($miembro->fresh()->jlis)->toBe(13); // ⌈50/4⌉
    }
});

it('entregas simultáneas de integrantes de la misma patrulla: solo pasa una', function () {
    $e = escenario(6);
    $mision = misionPara($e->unidad, [], 'porPatrulla');

    $tareas = $e->miembros->map(fn ($m) => tareaEntregarMision($m->id, $mision->id))->all();
    $resultados = array_count_values(Concurrency::driver('process')->run($tareas));

    expect($resultados['ok'])->toBe(1)
        ->and($resultados['bloqueada'])->toBe(5)
        ->and(Entrega::where('mision_id', $mision->id)->count())->toBe(1);
});

it('la misma persona entregando varias veces a la vez: solo pasa una', function () {
    $e = escenario();
    $mision = misionPara($e->unidad);

    $tareas = array_map(fn () => tareaEntregarMision($e->miembros[0]->id, $mision->id), range(1, 6));
    $resultados = array_count_values(Concurrency::driver('process')->run($tareas));

    expect($resultados['ok'])->toBe(1)->and(Entrega::count())->toBe(1);
});
