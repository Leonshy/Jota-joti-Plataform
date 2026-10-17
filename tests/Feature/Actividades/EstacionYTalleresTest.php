<?php

use App\Domain\Actividades\ChoqueDeHorario;
use App\Domain\Actividades\EstacionEnCurso;
use App\Domain\Actividades\Reservar;
use App\Models\Reserva;

// Una estación JOTA reservada NO impide apuntarse a talleres: solo cuenta que los horarios no se pisen.

beforeEach(function () {
    $this->e = escenario(3);
    [$this->yo, $this->compa] = [$this->e->miembros[0], $this->e->miembros[1]];
    $this->reservar = fn ($user, $turno) => app(Reservar::class)($user, $turno);
    $this->estacion = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Radio'], porEquipo: true), '+1 hour', '+2 hours', cupo: 1); // 01:00–02:00
    ($this->reservar)($this->yo, $this->estacion); // el equipo queda inscripto a la estación
});

it('con una estación a la 01:00 se puede apuntar a un taller a las 03:00', function () {
    $taller = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Nudos']), '+3 hours', '+4 hours', cupo: 5);

    ($this->reservar)($this->yo, $taller);

    expect(Reserva::where('user_id', $this->yo->id)->where('turno_id', $taller->id)->exists())->toBeTrue()
        ->and(Reserva::where('patrulla_id', $this->e->patrulla->id)->count())->toBe(1);
});

it('un taller que empieza justo cuando termina la estación también se puede (terminar y empezar no es choque)', function () {
    $taller = turnoDe(actividadPara($this->e->unidad), '+2 hours', '+3 hours', cupo: 5);

    expect(($this->reservar)($this->yo, $taller))->toBeInstanceOf(Reserva::class);
});

it('un taller que se pisa con la estación sí choca, para quien reservó y para cualquier integrante del equipo', function () {
    $pisa = turnoDe(actividadPara($this->e->unidad), '+90 minutes', '+150 minutes', cupo: 5);

    expect(fn () => ($this->reservar)($this->yo, $pisa))->toThrow(ChoqueDeHorario::class)
        ->and(fn () => ($this->reservar)($this->compa, $pisa))->toThrow(ChoqueDeHorario::class);
});

it('y lo que sí está limitado es otra ESTACIÓN: mientras la reservada no termine, el equipo no puede reservar otra', function () {
    $otraEstacion = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Gran juego'], porEquipo: true), '+5 hours', '+6 hours', cupo: 1);

    expect(fn () => ($this->reservar)($this->yo, $otraEstacion))->toThrow(EstacionEnCurso::class);
});
