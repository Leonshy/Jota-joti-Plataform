<?php

use App\Domain\Actividades\CancelarReserva;
use App\Domain\Actividades\ChoqueDeHorario;
use App\Domain\Actividades\ReservaDuplicada;
use App\Domain\Actividades\Reservar;
use App\Domain\Actividades\TallerYaApuntado;
use App\Domain\Actividades\TurnoLleno;
use App\Enums\Modalidad;
use App\Livewire\Beneficiario\Agenda;
use App\Models\Reserva;
use Livewire\Livewire;

// Reglas de los talleres: varios turnos, cada uno con su cupo; la gente se APUNTA, un turno por taller,
// y se pueden cursar varios talleres a la vez mientras los horarios no se pisen.

beforeEach(function () {
    $this->e = escenario(3);
    [$this->yo, $this->compa] = [$this->e->miembros[0], $this->e->miembros[1]];
    $this->apuntar = fn ($user, $turno) => app(Reservar::class)($user, $turno);
});

it('un turno de taller descuenta un lugar por persona hasta llenarse', function () {
    $turno = turnoDe(actividadPara($this->e->unidad), '+1 hour', '+2 hours', cupo: 2);

    ($this->apuntar)($this->e->miembros[0], $turno);
    ($this->apuntar)($this->e->miembros[1], $turno);

    expect(fn () => ($this->apuntar)($this->e->miembros[2], $turno))->toThrow(TurnoLleno::class);
});

it('quien ya se apuntó a un turno de un taller no puede apuntarse a otro turno del mismo taller', function () {
    $taller = actividadPara($this->e->unidad, ['titulo' => 'Nudos']);
    $manana = turnoDe($taller, '+1 hour', '+2 hours', cupo: 5);
    $tarde = turnoDe($taller, '+5 hours', '+6 hours', cupo: 5);

    ($this->apuntar)($this->yo, $manana);

    expect(fn () => ($this->apuntar)($this->yo, $tarde))->toThrow(TallerYaApuntado::class)
        ->and(fn () => ($this->apuntar)($this->yo, $manana))->toThrow(ReservaDuplicada::class);
    expect(Reserva::where('user_id', $this->yo->id)->count())->toBe(1);
    // Otro participante sí puede elegir el otro turno.
    ($this->apuntar)($this->compa, $tarde);
    expect(Reserva::count())->toBe(2);
});

it('si cancela su turno, puede apuntarse a otro del mismo taller', function () {
    $taller = actividadPara($this->e->unidad);
    $primero = turnoDe($taller, '+1 hour', '+2 hours', cupo: 5);
    $segundo = turnoDe($taller, '+5 hours', '+6 hours', cupo: 5);

    $reserva = ($this->apuntar)($this->yo, $primero);
    app(CancelarReserva::class)($this->yo, $reserva);
    ($this->apuntar)($this->yo, $segundo);

    expect(Reserva::where('user_id', $this->yo->id)->sole()->turno_id)->toBe($segundo->id);
});

it('un turno que ya pasó también cuenta: no se vuelve a apuntar al mismo taller', function () {
    $taller = actividadPara($this->e->unidad);
    $pasado = turnoDe($taller, '-3 hours', '-2 hours', cupo: 5);
    $futuro = turnoDe($taller, '+1 hour', '+2 hours', cupo: 5);
    Reserva::create(['turno_id' => $pasado->id, 'user_id' => $this->yo->id]);

    expect(fn () => ($this->apuntar)($this->yo, $futuro))->toThrow(TallerYaApuntado::class);
});

it('se puede estar apuntado a varios talleres distintos a la vez, mientras no se pisen los horarios', function () {
    $nudos = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Nudos']), '+1 hour', '+2 hours', cupo: 5);
    $auxilios = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Auxilios']), '+2 hours', '+3 hours', cupo: 5);
    $radio = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Radio']), '+4 hours', '+5 hours', cupo: 5);

    foreach ([$nudos, $auxilios, $radio] as $turno) {
        ($this->apuntar)($this->yo, $turno);
    }

    expect(Reserva::where('user_id', $this->yo->id)->count())->toBe(3);
});

it('no se puede estar apuntado a dos talleres a la misma hora', function () {
    $a = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Nudos']), '+1 hour', '+2 hours', cupo: 5);
    $b = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Auxilios']), '+90 minutes', '+150 minutes', cupo: 5);

    ($this->apuntar)($this->yo, $a);

    expect(fn () => ($this->apuntar)($this->yo, $b))->toThrow(ChoqueDeHorario::class);
});

it('si la patrulla se apuntó a un taller a una hora, ni la patrulla ni sus integrantes pueden apuntarse a otro a esa hora', function () {
    $porEquipo = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Postas', 'modalidad' => Modalidad::Patrulla]), '+1 hour', '+2 hours', cupo: 3);
    $individual = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Nudos']), '+90 minutes', '+150 minutes', cupo: 5);
    $otroEquipo = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Mapas', 'modalidad' => Modalidad::Patrulla]), '+90 minutes', '+150 minutes', cupo: 3);

    ($this->apuntar)($this->yo, $porEquipo);

    expect(fn () => ($this->apuntar)($this->compa, $individual))->toThrow(ChoqueDeHorario::class)
        ->and(fn () => ($this->apuntar)($this->compa, $otroEquipo))->toThrow(ChoqueDeHorario::class);
});

it('un taller por equipo se cursa una sola vez por patrulla, y a cualquier integrante le vale', function () {
    $taller = actividadPara($this->e->unidad, ['titulo' => 'Postas', 'modalidad' => Modalidad::Patrulla]);
    $uno = turnoDe($taller, '+1 hour', '+2 hours', cupo: 3);
    $dos = turnoDe($taller, '+5 hours', '+6 hours', cupo: 3);

    ($this->apuntar)($this->yo, $uno);

    $error = null;
    try {
        ($this->apuntar)($this->compa, $dos);
    } catch (TallerYaApuntado $e) {
        $error = $e;
    }

    expect($error)->not->toBeNull()->and($error->porEquipo)->toBeTrue();
    expect(Reserva::where('patrulla_id', $this->e->patrulla->id)->count())->toBe(1);
});

it('la agenda dice «Apuntarme», avisa al apuntarse y deja los otros turnos del taller sin botón', function () {
    $taller = actividadPara($this->e->unidad, ['titulo' => 'Nudos']);
    $manana = turnoDe($taller, '+1 hour', '+2 hours', cupo: 5);
    turnoDe($taller, '+5 hours', '+6 hours', cupo: 5);

    $html = $this->actingAs($this->yo)->get(route('app.actividades'))->assertOk()->assertSee('Apuntarme')->assertDontSee('Reservar')->getContent();
    expect(substr_count($html, 'wire:click="reservar('))->toBe(2);

    Livewire::actingAs($this->yo)->test(Agenda::class)
        ->call('reservar', $manana->id)->assertSet('tono', 'exito')->assertSee('Te apuntaste a Nudos');

    $html = $this->actingAs($this->yo)->get(route('app.actividades'))->assertOk()->assertSee('Apuntado')->assertSee('Ya estás apuntado a este taller')->getContent();
    expect(substr_count($html, 'wire:click="reservar('))->toBe(0);
});

it('al intentar apuntarse a otro turno del mismo taller, la agenda explica por qué no', function () {
    $taller = actividadPara($this->e->unidad, ['titulo' => 'Nudos']);
    $primero = turnoDe($taller, '+1 hour', '+2 hours', cupo: 5);
    $segundo = turnoDe($taller, '+5 hours', '+6 hours', cupo: 5);
    ($this->apuntar)($this->yo, $primero);

    Livewire::actingAs($this->yo)->test(Agenda::class)
        ->call('reservar', $segundo->id)->assertSet('tono', 'aviso')->assertSee('Ya estás apuntado a «Nudos» en otro horario');
});

it('en las estaciones JOTA se sigue diciendo «Reservar»', function () {
    $estacion = actividadPara($this->e->unidad, ['titulo' => 'Radio'], porEquipo: true);
    turnoDe($estacion, '+1 hour', '+2 hours', cupo: 1);

    $this->actingAs($this->yo)->get(route('app.actividades'))->assertOk()->assertSee('Reservar')->assertDontSee('Apuntarme');
});
