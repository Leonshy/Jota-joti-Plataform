<?php

use App\Domain\Actividades\AbrirSesionAsistencia;
use App\Domain\Actividades\TokenRotativo;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Asistencia;
use App\Models\CumplimientoEquipo;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\MovPuntoPatrulla;
use App\Models\Reserva;
use App\Models\SesionAsistencia;
use App\Models\User;
use Illuminate\Support\Facades\Concurrency;

function concurrentes(array $tareas): array
{
    return array_count_values(Concurrency::driver('process')->run($tareas));
}

it('el cupo no se excede con 10 reservas simultáneas de un turno de 3 lugares', function () {
    $e = escenario(10);
    $turno = turnoDe(actividadPara($e->unidad), cupo: 3);

    $r = concurrentes($e->miembros->map(fn ($m) => tareaReservar($m->id, $turno->id))->all());

    expect($r['ok'])->toBe(3)->and($r['lleno'])->toBe(7)
        ->and(Reserva::where('turno_id', $turno->id)->count())->toBe(3);
});

it('la misma persona reservando a la vez dos turnos que se pisan: pasa solo uno', function () {
    $e = escenario(1);
    $a = turnoDe(actividadPara($e->unidad), '+1 hour', '+2 hours');
    $b = turnoDe(actividadPara($e->unidad), '+90 minutes', '+150 minutes');
    $c = turnoDe(actividadPara($e->unidad), '+100 minutes', '+130 minutes');
    $yo = $e->miembros[0];

    $r = concurrentes([tareaReservar($yo->id, $a->id), tareaReservar($yo->id, $b->id), tareaReservar($yo->id, $c->id)]);

    expect($r['ok'])->toBe(1)->and($r['choque'])->toBe(2)->and(Reserva::count())->toBe(1);
});

it('una reserva individual y una de patrulla que se pisan, a la vez: pasa solo una', function () {
    $e = escenario(4);
    $individual = turnoDe(actividadPara($e->unidad), '+1 hour', '+2 hours');
    $equipo = turnoDe(actividadPara($e->unidad, [], true), '+90 minutes', '+150 minutes');

    // Un integrante reserva su turno individual mientras otro reserva por toda la patrulla.
    $r = concurrentes([tareaReservar($e->miembros[0]->id, $individual->id), tareaReservar($e->miembros[1]->id, $equipo->id)]);

    expect($r['ok'])->toBe(1)->and($r['choque'])->toBe(1)->and(Reserva::count())->toBe(1);
});

it('dos integrantes reservando a la vez por la patrulla: una sola reserva', function () {
    $e = escenario(4);
    $turno = turnoDe(actividadPara($e->unidad, [], true));

    $r = concurrentes($e->miembros->map(fn ($m) => tareaReservar($m->id, $turno->id))->all());

    expect($r['ok'])->toBe(1)->and($r['duplicada'])->toBe(3)->and(Reserva::count())->toBe(1);
});

it('la misma persona apuntándose a la vez a cuatro turnos del mismo taller: pasa solo uno', function () {
    $e = escenario(1);
    $taller = actividadPara($e->unidad);
    $turnos = [
        turnoDe($taller, '+1 hour', '+2 hours', cupo: 5), turnoDe($taller, '+3 hours', '+4 hours', cupo: 5),
        turnoDe($taller, '+5 hours', '+6 hours', cupo: 5), turnoDe($taller, '+7 hours', '+8 hours', cupo: 5),
    ];

    $r = concurrentes(array_map(fn ($t) => tareaReservar($e->miembros[0]->id, $t->id), $turnos));

    expect($r['ok'])->toBe(1)->and($r['taller ya apuntado'])->toBe(3)
        ->and(Reserva::where('user_id', $e->miembros[0]->id)->count())->toBe(1);
});

it('cuatro integrantes apuntando a la vez a la patrulla en turnos distintos del mismo taller: pasa solo uno', function () {
    $e = escenario(4);
    $taller = actividadPara($e->unidad, ['modalidad' => Modalidad::Patrulla]);
    $turnos = [
        turnoDe($taller, '+1 hour', '+2 hours', cupo: 5), turnoDe($taller, '+3 hours', '+4 hours', cupo: 5),
        turnoDe($taller, '+5 hours', '+6 hours', cupo: 5), turnoDe($taller, '+7 hours', '+8 hours', cupo: 5),
    ];

    $r = concurrentes($e->miembros->map(fn ($m, $i) => tareaReservar($m->id, $turnos[$i]->id))->all());

    expect($r['ok'])->toBe(1)->and($r['taller ya apuntado'])->toBe(3)
        ->and(Reserva::where('patrulla_id', $e->patrulla->id)->count())->toBe(1);
});

it('doble escaneo simultáneo: un solo registro y una sola acreditación', function () {
    $e = escenario(1);
    $actividad = actividadPara($e->unidad, ['puntos' => 20]);
    $sesion = app(AbrirSesionAsistencia::class)($e->dirigente, $actividad);
    $token = app(TokenRotativo::class)->generar($sesion);

    $r = concurrentes(array_map(fn () => tareaEscanear($e->miembros[0]->id, $sesion->id, $token), range(1, 8)));

    expect($r['ok'])->toBe(1)->and($r['ya registrada'])->toBe(7)
        ->and(Asistencia::count())->toBe(1)
        ->and(MovPuntoIndividual::count())->toBe(1)->and(MovJli::count())->toBe(1)
        ->and($e->miembros[0]->fresh()->puntos)->toBe(20)->and($e->miembros[0]->fresh()->jlis)->toBe(20);
});

it('muchos participantes escaneando a la vez: cada uno suma una vez', function () {
    $e = escenario(8);
    $actividad = actividadPara($e->unidad, ['puntos' => 10]);
    $sesion = app(AbrirSesionAsistencia::class)($e->dirigente, $actividad);
    $token = app(TokenRotativo::class)->generar($sesion);

    $r = concurrentes($e->miembros->map(fn ($m) => tareaEscanear($m->id, $sesion->id, $token))->all());

    expect($r['ok'])->toBe(8)->and(Asistencia::count())->toBe(8)
        ->and((int) MovPuntoIndividual::sum('cantidad'))->toBe(80);
});

it('doble marca de cumplida simultánea: puntos y LisCoins una sola vez', function () {
    $e = escenario(4);
    $actividad = actividadPara($e->unidad, ['puntos' => 50], true);

    $r = concurrentes(array_map(fn () => tareaMarcarCumplida($e->dirigente->id, $actividad->id, $e->patrulla->id), range(1, 8)));

    expect($r['ok'])->toBe(1)->and($r['ya acreditado'])->toBe(7)
        ->and(CumplimientoEquipo::count())->toBe(1)
        ->and($e->patrulla->fresh()->puntos)->toBe(50)
        ->and(MovPuntoPatrulla::count())->toBe(1)
        ->and(MovJli::count())->toBe(4);

    foreach ($e->miembros as $m) {
        expect($m->fresh()->jlis)->toBe(13); // ⌈50/4⌉
    }
});

it('dos dirigentes abriendo sesión a la vez: queda una sola abierta', function () {
    $e = escenario(1);
    $otro = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => $e->unidad->id]);
    $actividad = actividadPara($e->unidad);

    $r = concurrentes([tareaAbrirSesion($e->dirigente->id, $actividad->id), tareaAbrirSesion($otro->id, $actividad->id)]);

    expect($r['ok'])->toBe(1)->and($r['ya abierta'])->toBe(1)
        ->and(SesionAsistencia::whereNull('cerrada_at')->count())->toBe(1);
});
