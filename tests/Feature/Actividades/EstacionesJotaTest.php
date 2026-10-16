<?php

use App\Domain\Actividades\CancelarReserva;
use App\Domain\Actividades\ChoqueDeHorario;
use App\Domain\Actividades\CumplimientoYaAcreditado;
use App\Domain\Actividades\EstacionEnCurso;
use App\Domain\Actividades\MarcarCumplimiento;
use App\Domain\Actividades\ModalidadIncorrecta;
use App\Domain\Actividades\ReservaDuplicada;
use App\Domain\Actividades\Reservar;
use App\Domain\Actividades\SinPatrulla;
use App\Domain\Actividades\TurnoLleno;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Enums\TipoActividad;
use App\Livewire\Beneficiario\Agenda;
use App\Models\Actividad;
use App\Models\Reserva;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->e = escenario(3);
    $this->otra = escenario(3);       // otra patrulla de la misma unidad
    User::whereIn('id', $this->otra->miembros->pluck('id'))->update(['unidad_id' => $this->e->unidad->id]);
    $this->otra->patrulla->update(['unidad_id' => $this->e->unidad->id]);
    $this->otra->miembros = $this->otra->miembros->map(fn ($m) => $m->fresh()); // con la unidad nueva
    $this->reservar = app(Reservar::class);
});

function estacion($t, string $titulo = 'Estación', ?Unidad $unidad = null): Actividad
{
    return actividadPara($unidad ?? $t->e->unidad, ['titulo' => $titulo], porEquipo: true);
}

// ── Reglas de la estación ───────────────────────────────────────────────────

it('una estación JOTA es siempre por equipo y cada horario es de un solo equipo', function () {
    $a = Actividad::factory()->create(['tipo' => TipoActividad::EstacionJota, 'modalidad' => Modalidad::Individual]);
    $turno = turnoDe($a, '+1 hour', '+2 hours', cupo: 8);

    expect($a->fresh()->modalidad)->toBe(Modalidad::Patrulla)->and($turno->fresh()->cupo)->toBe(1);

    // un taller sí puede ser individual y tener varios lugares
    $taller = Actividad::factory()->create(['tipo' => TipoActividad::Taller, 'modalidad' => Modalidad::Individual]);
    expect($taller->fresh()->modalidad)->toBe(Modalidad::Individual)->and(turnoDe($taller, '+1 hour', '+2 hours', cupo: 8)->fresh()->cupo)->toBe(8);
});

it('el horario de una estación lo toma un solo equipo: el segundo equipo lo ve ocupado', function () {
    $turno = turnoDe(estacion($this), '+1 hour', '+2 hours');

    ($this->reservar)($this->e->miembros[0], $turno);

    expect(fn () => ($this->reservar)($this->otra->miembros[0], $turno))->toThrow(TurnoLleno::class);
    expect(fn () => ($this->reservar)($this->e->miembros[1], $turno))->toThrow(ReservaDuplicada::class);
});

// ── Una estación a la vez ───────────────────────────────────────────────────

it('un equipo con una estación reservada no puede reservar otra, a ninguna hora, hasta que termine', function () {
    $a = estacion($this, 'Radio');
    $b = estacion($this, 'Cocina');
    ($this->reservar)($this->e->miembros[0], turnoDe($a, '+1 hour', '+2 hours'));

    // otra estación, a una hora que no se pisa: igual no
    expect(fn () => ($this->reservar)($this->e->miembros[1], turnoDe($b, '+5 hours', '+6 hours')))->toThrow(EstacionEnCurso::class);
    // otro horario de la misma estación: tampoco
    expect(fn () => ($this->reservar)($this->e->miembros[0], turnoDe($a, '+7 hours', '+8 hours')))->toThrow(EstacionEnCurso::class);

    // el otro equipo no se ve afectado
    expect(($this->reservar)($this->otra->miembros[0], turnoDe($b, '+5 hours', '+6 hours')))->toBeInstanceOf(Reserva::class);
});

it('el mensaje dice cuál es la estación y a qué hora', function () {
    ($this->reservar)($this->e->miembros[0], turnoDe(estacion($this, 'Radio'), '+1 hour', '+2 hours'));

    try {
        ($this->reservar)($this->e->miembros[1], turnoDe(estacion($this, 'Cocina'), '+5 hours', '+6 hours'));
    } catch (EstacionEnCurso $e) {
        expect($e->actividad)->toBe('Radio');
    }
});

it('al cancelar la reserva, o al terminar la estación, el equipo puede reservar otra', function () {
    $a = estacion($this, 'Radio');
    $reserva = ($this->reservar)($this->e->miembros[0], turnoDe($a, '+1 hour', '+2 hours'));
    $b = turnoDe(estacion($this, 'Cocina'), '+5 hours', '+6 hours');

    app(CancelarReserva::class)($this->e->miembros[0], $reserva);
    $segunda = ($this->reservar)($this->e->miembros[1], $b);
    expect($segunda)->toBeInstanceOf(Reserva::class);

    // pasa la hora de la segunda estación: ya terminó, puede reservar la tercera
    $this->travel(7)->hours();
    $c = turnoDe(estacion($this, 'Radio 2'), '+1 hour', '+2 hours');
    expect(($this->reservar)($this->e->miembros[2], $c))->toBeInstanceOf(Reserva::class);
});

it('los talleres individuales y por equipo no cuentan: la regla es solo de las estaciones', function () {
    ($this->reservar)($this->e->miembros[0], turnoDe(estacion($this), '+1 hour', '+2 hours'));

    $taller = actividadPara($this->e->unidad, ['tipo' => TipoActividad::Taller]);

    expect(($this->reservar)($this->e->miembros[1], turnoDe($taller, '+5 hours', '+6 hours')))->toBeInstanceOf(Reserva::class);
});

it('la agenda avisa que el equipo ya tiene una estación y no deja reservar otra', function () {
    ($this->reservar)($this->e->miembros[0], turnoDe(estacion($this, 'Radio'), '+1 hour', '+2 hours'));
    $otra = turnoDe(estacion($this, 'Cocina'), '+5 hours', '+6 hours');

    $c = Livewire::actingAs($this->e->miembros[1])->test(Agenda::class)->assertSee('ya tiene reservada «Radio»');
    $c->call('reservar', $otra->id)->assertSet('tono', 'aviso')->assertSee('Pueden reservar otra estación cuando termine');
    expect(Reserva::where('turno_id', $otra->id)->count())->toBe(0);
});

// ── Rovers: el equipo es toda la unidad ─────────────────────────────────────

beforeEach(function () {
    $this->rovers = Unidad::factory()->individual()->create(['nombre' => 'Rovers']);
    $this->clan = User::factory()->count(3)->create(['unidad_id' => $this->rovers->id, 'patrulla_id' => null]);
    $this->dirRover = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => $this->rovers->id]);
});

it('los Rovers reservan la estación como una sola unidad', function () {
    $turno = turnoDe(estacion($this, 'Radio', $this->rovers), '+1 hour', '+2 hours');

    $r = ($this->reservar)($this->clan[0], $turno);

    expect($r->unidad_id)->toBe($this->rovers->id)->and($r->user_id)->toBeNull()->and($r->patrulla_id)->toBeNull();
    expect(fn () => ($this->reservar)($this->clan[1], $turno))->toThrow(ReservaDuplicada::class);
});

it('un Rover no puede reservar otra estación mientras su unidad tiene una, y cualquiera de la unidad puede cancelarla', function () {
    $r = ($this->reservar)($this->clan[0], turnoDe(estacion($this, 'Radio', $this->rovers), '+1 hour', '+2 hours'));

    expect(fn () => ($this->reservar)($this->clan[2], turnoDe(estacion($this, 'Cocina', $this->rovers), '+5 hours', '+6 hours')))->toThrow(EstacionEnCurso::class);

    app(CancelarReserva::class)($this->clan[2], $r); // otro Rover de la unidad
    expect(Reserva::count())->toBe(0);
});

it('la reserva de la unidad choca con un taller individual de cualquier Rover', function () {
    $taller = actividadPara($this->rovers, [], false);
    ($this->reservar)($this->clan[1], turnoDe($taller, '+90 minutes', '+150 minutes'));

    expect(fn () => ($this->reservar)($this->clan[0], turnoDe(estacion($this, 'Radio', $this->rovers), '+1 hour', '+2 hours')))->toThrow(ChoqueDeHorario::class);
});

it('y a la inversa: un Rover no puede reservar un taller que se pisa con la estación de su unidad', function () {
    ($this->reservar)($this->clan[0], turnoDe(estacion($this, 'Radio', $this->rovers), '+1 hour', '+2 hours'));

    expect(fn () => ($this->reservar)($this->clan[1], turnoDe(actividadPara($this->rovers), '+90 minutes', '+150 minutes')))->toThrow(ChoqueDeHorario::class);
});

it('quien es de Tropa o Caminantes y no tiene patrulla no puede reservar una estación', function () {
    $sinPatrulla = User::factory()->create(['unidad_id' => $this->e->unidad->id, 'patrulla_id' => null]);

    expect(fn () => ($this->reservar)($sinPatrulla, turnoDe(estacion($this), '+1 hour', '+2 hours')))->toThrow(SinPatrulla::class);
});

it('una reserva de la unidad Rover no la puede cancelar otra unidad', function () {
    $r = ($this->reservar)($this->clan[0], turnoDe(estacion($this, 'Radio', $this->rovers), '+1 hour', '+2 hours'));

    expect(fn () => app(CancelarReserva::class)($this->e->miembros[0], $r))->toThrow(AuthorizationException::class);
    app(CancelarReserva::class)($this->dirRover, $r);
    expect(Reserva::count())->toBe(0);
});

// ── Cumplida de los Rovers: cada uno recibe los puntos completos ────────────

it('al cumplir la estación, cada Rover recibe los puntos y los LisCoins completos, una sola vez', function () {
    $a = actividadPara($this->rovers, ['puntos' => 60], porEquipo: true);

    app(MarcarCumplimiento::class)($this->dirRover, $a, $this->rovers, true);

    foreach ($this->clan as $rover) {
        expect($rover->fresh())->puntos->toBe(60)->jlis->toBe(60);
    }
    expect(fn () => app(MarcarCumplimiento::class)($this->dirRover, $a, $this->rovers, true))->toThrow(CumplimientoYaAcreditado::class);
    expect($this->clan[0]->fresh()->puntos)->toBe(60);
    $this->artisan('jota:reconciliar')->assertSuccessful();
});

it('no se marca la unidad entera en Tropa o Caminantes ni desde otra unidad', function () {
    $a = actividadPara($this->e->unidad, [], porEquipo: true);
    expect(fn () => app(MarcarCumplimiento::class)($this->e->dirigente, $a, $this->e->unidad, true))->toThrow(ModalidadIncorrecta::class);

    $rover = actividadPara($this->rovers, [], porEquipo: true);
    expect(fn () => app(MarcarCumplimiento::class)($this->e->dirigente, $rover, $this->rovers, true))->toThrow(AuthorizationException::class);
});
