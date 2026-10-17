<?php

use App\Domain\Actividades\CancelarReserva;
use App\Domain\Actividades\ChoqueDeHorario;
use App\Domain\Actividades\ReservaDuplicada;
use App\Domain\Actividades\Reservar;
use App\Domain\Actividades\SinPatrulla;
use App\Domain\Actividades\TurnoLleno;
use App\Domain\Actividades\TurnoYaComenzo;
use App\Domain\Actividades\TurnoYaTermino;
use App\Enums\Rol;
use App\Enums\TipoActividad;
use App\Models\Patrulla;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->e = escenario(4);
    $this->yo = $this->e->miembros[0];
    $this->reservar = app(Reservar::class);
});

// ── Individuales ────────────────────────────────────────────────────────────

it('reserva un turno individual a nombre de la persona', function () {
    $turno = turnoDe(actividadPara($this->e->unidad));

    $reserva = ($this->reservar)($this->yo, $turno);

    expect($reserva->user_id)->toBe($this->yo->id)->and($reserva->patrulla_id)->toBeNull();
});

it('no se puede reservar dos veces el mismo turno', function () {
    $turno = turnoDe(actividadPara($this->e->unidad));
    ($this->reservar)($this->yo, $turno);

    ($this->reservar)($this->yo, $turno);
})->throws(ReservaDuplicada::class);

it('respeta el cupo y lo libera al cancelar', function () {
    $turno = turnoDe(actividadPara($this->e->unidad), cupo: 2);
    $a = ($this->reservar)($this->e->miembros[0], $turno);
    ($this->reservar)($this->e->miembros[1], $turno);

    expect(fn () => ($this->reservar)($this->e->miembros[2], $turno))->toThrow(TurnoLleno::class);

    app(CancelarReserva::class)($this->e->miembros[0], $a);
    ($this->reservar)($this->e->miembros[2], $turno);

    expect($turno->reservas()->count())->toBe(2);
});

it('sin cupo definido no hay tope', function () {
    $turno = turnoDe(actividadPara($this->e->unidad), cupo: null);

    foreach ($this->e->miembros as $miembro) {
        ($this->reservar)($miembro, $turno);
    }

    expect($turno->reservas()->count())->toBe(4);
});

it('no se reserva una actividad de otra unidad ni como dirigente', function () {
    $ajena = turnoDe(actividadPara(escenario()->unidad));
    $propia = turnoDe(actividadPara($this->e->unidad));

    expect(fn () => ($this->reservar)($this->yo, $ajena))->toThrow(AuthorizationException::class)
        ->and(fn () => ($this->reservar)($this->e->dirigente, $propia))->toThrow(AuthorizationException::class);
});

it('en un TALLER se puede anotar hasta que el turno termina, aunque ya haya empezado', function () {
    $enCurso = turnoDe(actividadPara($this->e->unidad), '-10 minutes', '+50 minutes', cupo: 5);

    expect(($this->reservar)($this->yo, $enCurso))->toBeInstanceOf(Reserva::class);
});

it('en un taller que ya TERMINÓ no se puede anotar', function () {
    $terminado = turnoDe(actividadPara($this->e->unidad), '-2 hours', '-1 hour', cupo: 5);

    expect(fn () => ($this->reservar)($this->yo, $terminado))->toThrow(TurnoYaTermino::class);
    expect(Reserva::count())->toBe(0);
});

it('en una ESTACIÓN JOTA no se reserva un turno que ya empezó', function () {
    $turno = turnoDe(actividadPara($this->e->unidad, [], true), '-10 minutes', '+50 minutes');

    ($this->reservar)($this->yo, $turno);
})->throws(TurnoYaComenzo::class);

// ── Por equipo ──────────────────────────────────────────────────────────────

it('en actividades por equipo reserva cualquier integrante en nombre de la patrulla (D2)', function () {
    $turno = turnoDe(actividadPara($this->e->unidad, [], true));

    $reserva = ($this->reservar)($this->e->miembros[2], $turno);

    expect($reserva->patrulla_id)->toBe($this->e->patrulla->id)->and($reserva->user_id)->toBeNull();
});

it('la patrulla no puede reservar dos veces el mismo turno, lo intente quien lo intente', function () {
    $turno = turnoDe(actividadPara($this->e->unidad, [], true));
    ($this->reservar)($this->e->miembros[0], $turno);

    ($this->reservar)($this->e->miembros[1], $turno);
})->throws(ReservaDuplicada::class);

it('el cupo de una actividad por equipo cuenta patrullas (D5)', function () {
    $turno = turnoDe(actividadPara($this->e->unidad, [], true), cupo: 1);
    $otra = Patrulla::factory()->create(['unidad_id' => $this->e->unidad->id]);
    $deOtra = User::factory()->create(['unidad_id' => $this->e->unidad->id, 'patrulla_id' => $otra->id]);

    ($this->reservar)($this->e->miembros[0], $turno);

    expect(fn () => ($this->reservar)($deOtra, $turno))->toThrow(TurnoLleno::class);
});

it('reservar por equipo exige tener patrulla', function () {
    $sin = User::factory()->create(['unidad_id' => $this->e->unidad->id]);

    ($this->reservar)($sin, turnoDe(actividadPara($this->e->unidad, [], true)));
})->throws(SinPatrulla::class);

// ── No superposición ────────────────────────────────────────────────────────

it('bloquea dos reservas individuales que se pisan, aunque sean de actividades distintas', function () {
    ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Nudos']), '+1 hour', '+2 hours'));

    try {
        ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad), '+90 minutes', '+150 minutes'));
        $this->fail('Debió chocar');
    } catch (ChoqueDeHorario $e) {
        expect($e->choques)->toHaveCount(1)
            ->and($e->choques[0]['actividad'])->toBe('Nudos')
            ->and($e->getMessage())->toContain('Nudos');
    }
});

it('terminar justo cuando otro turno empieza no es choque', function () {
    ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad), '+1 hour', '+2 hours'));

    ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad), '+2 hours', '+3 hours'));

    expect(Reserva::count())->toBe(2);
});

it('un turno contenido dentro de otro también choca', function () {
    ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad), '+1 hour', '+4 hours'));

    ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad), '+2 hours', '+3 hours'));
})->throws(ChoqueDeHorario::class);

it('las reservas de otra persona no bloquean', function () {
    $turno = turnoDe(actividadPara($this->e->unidad), '+1 hour', '+2 hours');
    ($this->reservar)($this->e->miembros[0], $turno);

    ($this->reservar)($this->e->miembros[1], turnoDe(actividadPara($this->e->unidad), '+1 hour', '+2 hours'));

    expect(Reserva::count())->toBe(2);
});

it('la reserva de la patrulla bloquea a un integrante que quiere una individual que se pisa', function () {
    ($this->reservar)($this->e->miembros[1], turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Gran juego'], true), '+1 hour', '+2 hours'));

    try {
        ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad), '+90 minutes', '+150 minutes'));
        $this->fail('Debió chocar');
    } catch (ChoqueDeHorario $e) {
        expect($e->choques[0]['quien'])->toContain($this->e->patrulla->nombre)
            ->and($e->choques[0]['actividad'])->toBe('Gran juego');
    }
});

it('reservar por la patrulla se bloquea si un integrante tiene una individual que se pisa y dice quién (D3)', function () {
    ($this->reservar)($this->e->miembros[3], turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Radio']), '+1 hour', '+2 hours'));

    try {
        ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad, [], true), '+90 minutes', '+150 minutes'));
        $this->fail('Debió chocar');
    } catch (ChoqueDeHorario $e) {
        expect($e->choques[0]['quien'])->toBe($this->e->miembros[3]->name)
            ->and($e->choques[0]['actividad'])->toBe('Radio');
    }
    expect(Reserva::whereNotNull('patrulla_id')->count())->toBe(0);
});

it('dos reservas de la misma patrulla que se pisan chocan', function () {
    // Talleres por equipo (las estaciones JOTA tienen su propia regla: una sola a la vez).
    $taller = fn () => actividadPara($this->e->unidad, ['tipo' => TipoActividad::Taller], true);

    ($this->reservar)($this->yo, turnoDe($taller(), '+1 hour', '+2 hours'));

    ($this->reservar)($this->e->miembros[1], turnoDe($taller(), '+90 minutes', '+150 minutes'));
})->throws(ChoqueDeHorario::class);

it('al cancelar la reserva que chocaba, se puede reservar el otro turno', function () {
    $primera = ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad), '+1 hour', '+2 hours'));
    $segundo = turnoDe(actividadPara($this->e->unidad), '+90 minutes', '+150 minutes');

    expect(fn () => ($this->reservar)($this->yo, $segundo))->toThrow(ChoqueDeHorario::class);

    app(CancelarReserva::class)($this->yo, $primera);
    ($this->reservar)($this->yo, $segundo);

    expect(Reserva::count())->toBe(1);
});

// ── Cancelar ────────────────────────────────────────────────────────────────

it('cancela quien reservó, y cualquier integrante la reserva de la patrulla', function () {
    $individual = ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad)));
    $equipo = ($this->reservar)($this->e->miembros[1], turnoDe(actividadPara($this->e->unidad, [], true), '+5 hours', '+6 hours'));

    app(CancelarReserva::class)($this->yo, $individual);
    app(CancelarReserva::class)($this->e->miembros[3], $equipo);

    expect(Reserva::count())->toBe(0);
});

it('no cancela una reserva ajena', function () {
    $reserva = ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad)));

    app(CancelarReserva::class)($this->e->miembros[1], $reserva);
})->throws(AuthorizationException::class);

it('no se cancela un turno que ya empezó, salvo el admin', function () {
    $turno = turnoDe(actividadPara($this->e->unidad));
    $reserva = ($this->reservar)($this->yo, $turno);
    $this->travel(90)->minutes();
    $admin = User::factory()->rol(Rol::Admin)->create();

    expect(fn () => app(CancelarReserva::class)($this->yo, $reserva))->toThrow(TurnoYaComenzo::class);

    app(CancelarReserva::class)($admin, $reserva);
    expect(Reserva::count())->toBe(0);
});

it('cancelar dos veces no falla', function () {
    $reserva = ($this->reservar)($this->yo, turnoDe(actividadPara($this->e->unidad)));

    app(CancelarReserva::class)($this->yo, $reserva);
    app(CancelarReserva::class)($this->yo, $reserva);

    expect(Reserva::count())->toBe(0);
});
