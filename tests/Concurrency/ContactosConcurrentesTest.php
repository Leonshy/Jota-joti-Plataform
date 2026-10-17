<?php

use App\Domain\Contactos\Puntaje;
use App\Enums\EstadoEntrega;
use App\Enums\Rol;
use App\Models\Contacto;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\MovPuntoPatrulla;
use App\Models\User;
use Illuminate\Support\Facades\Concurrency;

function enParalelo(array $tareas): array
{
    return array_count_values(Concurrency::driver('process')->run($tareas));
}

beforeEach(function () {
    app(Puntaje::class)->guardar(['jota_local' => 10, 'jota_exterior' => 30, 'joti_local' => 5, 'joti_exterior' => 15], adminDePrueba());
});

it('ocho dirigentes aprobando a la vez el mismo contacto JOTI: se acredita una sola vez', function () {
    $e = escenario(2);
    $otros = User::factory()->count(7)->rol(Rol::Dirigente)->create(['unidad_id' => $e->unidad->id]);
    $contacto = Contacto::factory()->create(['user_id' => $e->miembros[0]->id, 'unidad_id' => $e->unidad->id, 'es_local' => false]);

    $r = enParalelo($otros->push($e->dirigente)->map(fn ($d) => tareaAprobarContacto($contacto->id, $d->id))->all());

    expect($r['ok'])->toBe(1)->and($r['ya revisado'])->toBe(7)
        ->and($contacto->fresh()->estado)->toBe(EstadoEntrega::Aprobada)
        ->and(MovPuntoIndividual::count())->toBe(1)->and(MovJli::count())->toBe(1)
        ->and($e->miembros[0]->fresh()->puntos)->toBe(15)->and($e->miembros[0]->fresh()->jlis)->toBe(15);
});

it('aprobar a la vez el mismo contacto JOTA: puntos a la patrulla una vez y LisCoins repartidos una vez', function () {
    $e = escenario(4);
    $otros = User::factory()->count(5)->rol(Rol::Dirigente)->create(['unidad_id' => $e->unidad->id]);
    $contacto = Contacto::factory()->jota()->create(['user_id' => $e->miembros[0]->id, 'unidad_id' => $e->unidad->id, 'patrulla_id' => $e->patrulla->id, 'es_local' => true]);

    $r = enParalelo($otros->map(fn ($d) => tareaAprobarContacto($contacto->id, $d->id))->all());

    expect($r['ok'])->toBe(1)->and($r['ya revisado'])->toBe(4)
        ->and($e->patrulla->fresh()->puntos)->toBe(10)->and(MovPuntoPatrulla::count())->toBe(1)->and(MovJli::count())->toBe(4);

    foreach ($e->miembros as $m) {
        expect($m->fresh()->jlis)->toBe(3); // ⌈10/4⌉
    }
});

it('cuatro integrantes cargando a la vez el mismo contacto JOTA: queda uno solo', function () {
    $e = escenario(4);
    $datos = ['contactado_at' => now()->subHour()->format('Y-m-d H:i'), 'estacion' => 'ZP5ZDM', 'rst_tx' => '5-9', 'rst_rx' => '5-9', 'frecuencia' => '7.125', 'emision' => 'LSB'];

    $r = enParalelo($e->miembros->map(fn ($m) => tareaRegistrarJota($m->id, $datos))->all());

    expect($r['ok'])->toBe(1)->and($r['repetido'])->toBe(3)->and(Contacto::count())->toBe(1);
});
