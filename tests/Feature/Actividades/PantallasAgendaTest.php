<?php

use App\Domain\Actividades\AgendaDelParticipante;
use App\Livewire\Beneficiario\Agenda;
use App\Livewire\Beneficiario\MisReservas;
use App\Models\Reserva;
use App\Models\Unidad;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->e = escenario(3);
    $this->yo = $this->e->miembros[0];
});

it('la agenda muestra las actividades de mi unidad con sus turnos futuros, no las de otras', function () {
    $taller = actividadPara($this->e->unidad, ['titulo' => 'Taller de nudos']);
    turnoDe($taller, '+1 hour', '+2 hours', cupo: 5);
    turnoDe($taller, '-3 hours', '-2 hours'); // ya terminó
    actividadPara(Unidad::factory()->create(), ['titulo' => 'Actividad ajena']);

    $html = $this->actingAs($this->yo)->get(route('app.actividades'))->assertOk()
        ->assertSee('Taller de nudos')->assertDontSee('Actividad ajena')->getContent();

    expect(substr_count($html, 'wire:click="reservar('))->toBe(1);
});

it('calcula lugares, turnos en curso y reservados', function () {
    $a = actividadPara($this->e->unidad);
    $lleno = turnoDe($a, '+1 hour', '+2 hours', cupo: 1);
    $libre = turnoDe($a, '+3 hours', '+4 hours', cupo: 4);
    $enCurso = turnoDe($a, '-10 minutes', '+50 minutes');
    Reserva::create(['turno_id' => $lleno->id, 'user_id' => $this->e->miembros[1]->id]);
    Reserva::create(['turno_id' => $libre->id, 'user_id' => $this->yo->id]);

    $turnos = collect(app(AgendaDelParticipante::class)->actividades($this->yo)[0]['turnos'])->keyBy('id');

    expect($turnos[$lleno->id])->toMatchArray(['lugares' => 0, 'reservado' => false, 'comenzado' => false])
        ->and($turnos[$libre->id])->toMatchArray(['lugares' => 3, 'reservado' => true])
        ->and($turnos[$enCurso->id])->toMatchArray(['lugares' => null, 'comenzado' => true]);
});

it('reserva un turno y avisa', function () {
    $turno = turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Nudos']), '+1 hour', '+2 hours', cupo: 2);

    Livewire::actingAs($this->yo)->test(Agenda::class)
        ->call('reservar', $turno->id)->assertSet('tono', 'exito')->assertSee('Nudos');

    expect(Reserva::sole())->turno_id->toBe($turno->id)->user_id->toBe($this->yo->id);
});

it('una actividad por equipo se reserva para toda la patrulla y la ven los compañeros', function () {
    $turno = turnoDe(actividadPara($this->e->unidad, [], porEquipo: true));

    Livewire::actingAs($this->yo)->test(Agenda::class)->call('reservar', $turno->id)->assertSet('tono', 'exito');

    expect(Reserva::sole()->patrulla_id)->toBe($this->e->patrulla->id);
    $turnos = app(AgendaDelParticipante::class)->actividades($this->e->miembros[2])[0]['turnos'];
    expect($turnos[0]['reservado'])->toBeTrue();
});

it('informa lleno, duplicada, choque y turno ya empezado sin reservar de más', function () {
    $a = actividadPara($this->e->unidad);
    $lleno = turnoDe($a, '+1 hour', '+2 hours', cupo: 1);
    Reserva::create(['turno_id' => $lleno->id, 'user_id' => $this->e->miembros[1]->id]);
    $c = Livewire::actingAs($this->yo)->test(Agenda::class);

    $c->call('reservar', $lleno->id)->assertSet('tono', 'aviso')->assertSet('mensaje', __('jota.actividades.error_lleno'));

    $ok = turnoDe($a, '+3 hours', '+4 hours');
    $c->call('reservar', $ok->id)->assertSet('tono', 'exito');
    $c->call('reservar', $ok->id)->assertSet('mensaje', __('jota.actividades.error_duplicada'));

    $otra = actividadPara($this->e->unidad, ['titulo' => 'Otra']);
    $choca = turnoDe($otra, '+3 hours +30 minutes', '+5 hours');
    $c->call('reservar', $choca->id)->assertSet('tono', 'aviso')->assertSee(__('jota.actividades.error_choque_propio', ['actividad' => $a->titulo, 'inicio' => $ok->inicio->format('H:i'), 'fin' => $ok->fin->format('H:i')]));

    // Una estación que ya empezó no se reserva; un taller que ya terminó tampoco.
    $estacion = actividadPara($this->e->unidad, ['titulo' => 'Radio'], porEquipo: true);
    $c->call('reservar', turnoDe($estacion, '-5 minutes', '+1 hour', cupo: 1)->id)->assertSet('mensaje', __('jota.actividades.ya_empezo'));
    $c->call('reservar', turnoDe($a, '-3 hours', '-2 hours')->id)->assertSet('mensaje', __('jota.actividades.ya_termino'));

    expect(Reserva::where('user_id', $this->yo->id)->count())->toBe(1);
});

it('un taller en curso todavía deja anotarse: la agenda lo dice y el botón está activo; la estación en curso queda cerrada', function () {
    $taller = actividadPara($this->e->unidad, ['titulo' => 'IA para tus ideas']);
    $turno = turnoDe($taller, '-30 minutes', '+30 minutes', cupo: 50);
    $estacion = actividadPara($this->e->unidad, ['titulo' => 'Radio'], porEquipo: true);
    turnoDe($estacion, '-30 minutes', '+30 minutes', cupo: 1);

    $agenda = collect(app(AgendaDelParticipante::class)->actividades($this->yo))->keyBy('titulo');
    expect($agenda['IA para tus ideas']['turnos'][0])->toMatchArray(['comenzado' => true, 'cerrado' => false, 'lugares' => 50])
        ->and($agenda['Radio']['turnos'][0])->toMatchArray(['comenzado' => true, 'cerrado' => true]);

    $html = $this->actingAs($this->yo)->get(route('app.actividades'))->assertOk()->assertSee('En curso: todavía podés sumarte')->getContent();
    expect(substr_count($html, 'wire:click="reservar('))->toBe(1); // solo el del taller; la estación en curso no tiene botón activo

    Livewire::actingAs($this->yo)->test(Agenda::class)->call('reservar', $turno->id)->assertSet('tono', 'exito')->assertSee('Te apuntaste a IA para tus ideas');
});

it('no reserva en una actividad de otra unidad', function () {
    $ajena = turnoDe(actividadPara(Unidad::factory()->create()));

    Livewire::actingAs($this->yo)->test(Agenda::class)->call('reservar', $ajena->id)->assertForbidden();
    expect(Reserva::count())->toBe(0);
});

it('mis reservas lista las vigentes y cancela con confirmación', function () {
    $a = actividadPara($this->e->unidad, ['titulo' => 'Nudos']);
    $futuro = turnoDe($a, '+1 hour', '+2 hours');
    $pasado = turnoDe($a, '-3 hours', '-2 hours');
    $r = Reserva::create(['turno_id' => $futuro->id, 'user_id' => $this->yo->id]);
    Reserva::create(['turno_id' => $pasado->id, 'user_id' => $this->yo->id]);

    $c = Livewire::actingAs($this->yo)->test(MisReservas::class)->assertSee('Nudos');
    expect(app(AgendaDelParticipante::class)->reservas($this->yo))->toHaveCount(1);

    $c->call('pedirCancelar', $r->id)->assertSee(__('jota.actividades.cancelar_pregunta'));
    expect(Reserva::count())->toBe(2);

    $c->call('cancelar')->assertSet('confirmar', null);
    expect(Reserva::count())->toBe(1);
});

it('no se cancela una reserva ajena ni un turno que ya empezó', function () {
    $a = actividadPara($this->e->unidad);
    $ajena = Reserva::create(['turno_id' => turnoDe($a)->id, 'user_id' => escenario(1)->miembros[0]->id]);
    $empezada = Reserva::create(['turno_id' => turnoDe($a, '-5 minutes', '+1 hour')->id, 'user_id' => $this->yo->id]);

    Livewire::actingAs($this->yo)->test(MisReservas::class)->set('confirmar', $ajena->id)->call('cancelar')->assertForbidden();
    Livewire::actingAs($this->yo)->test(MisReservas::class)->set('confirmar', $empezada->id)->call('cancelar')->assertSet('mensaje', __('jota.actividades.ya_empezo'));

    expect(Reserva::count())->toBe(2);
});

it('cancelar una reserva de la patrulla la puede hacer cualquier integrante', function () {
    $r = Reserva::create(['turno_id' => turnoDe(actividadPara($this->e->unidad, [], porEquipo: true))->id, 'patrulla_id' => $this->e->patrulla->id]);

    Livewire::actingAs($this->e->miembros[2])->test(MisReservas::class)->set('confirmar', $r->id)->call('cancelar');

    expect(Reserva::count())->toBe(0);
});

it('la agenda usa pocas consultas aunque haya muchas actividades y turnos', function () {
    foreach (range(1, 8) as $_) {
        $a = actividadPara($this->e->unidad);
        foreach (range(1, 4) as $i) {
            turnoDe($a, "+{$i} hours", '+'.($i + 1).' hours');
        }
    }

    DB::enableQueryLog();
    app(AgendaDelParticipante::class)->actividades($this->yo);

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(4);
});

it('los dirigentes y sin sesión no entran a la agenda del participante', function () {
    $this->actingAs($this->e->dirigente)->get(route('app.actividades'))->assertForbidden();
    auth()->logout();
    $this->get(route('app.actividades'))->assertRedirect(route('ingresar'));
});
