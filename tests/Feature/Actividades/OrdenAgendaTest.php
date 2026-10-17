<?php

use App\Domain\Actividades\AgendaDelParticipante;
use App\Domain\Actividades\Reservar;
use App\Domain\Misiones\MisionesDelParticipante;
use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use App\Models\Reserva;

// Orden y visibilidad que pidió el grupo (17/10/2026).

beforeEach(function () {
    $this->e = escenario(4);
    [$this->yo, $this->otro] = [$this->e->miembros[0], $this->e->miembros[1]];
    $this->agenda = fn () => app(AgendaDelParticipante::class)->actividades($this->yo);
    $this->titulos = fn (bool $taller) => collect(($this->agenda)())->where('taller', $taller)->pluck('titulo')->all();
});

// ── Misiones ────────────────────────────────────────────────────────────────

it('las misiones se ordenan: rechazadas, nuevas, en revisión, aprobadas, vencidas y cerradas', function () {
    $m = fn (string $titulo, ?string $estado = null) => misionPara($this->e->unidad, ['titulo' => $titulo], $estado);
    $cerrada = $m('6 cerrada', 'cerrada');
    $vencida = $m('5 vencida', 'vencida');
    $aprobada = $m('4 aprobada');
    $pendiente = $m('3 revisión');
    $nueva = $m('2 nueva');
    $rechazada = $m('1 rechazada');
    foreach ([[$aprobada, EstadoEntrega::Aprobada], [$pendiente, EstadoEntrega::Pendiente], [$rechazada, EstadoEntrega::Rechazada]] as [$mision, $estado]) {
        Entrega::factory()->create(['mision_id' => $mision->id, 'user_id' => $this->yo->id, 'estado' => $estado, 'motivo_rechazo' => $estado === EstadoEntrega::Rechazada ? 'x' : null]);
    }

    $lista = collect(app(MisionesDelParticipante::class)->lista($this->yo));

    expect($lista->pluck('estado')->all())->toBe(['rechazada', 'nueva', 'pendiente', 'aprobada', 'vencida', 'cerrada'])
        ->and($lista->pluck('titulo')->all())->toBe(['1 rechazada', '2 nueva', '3 revisión', '4 aprobada', '5 vencida', '6 cerrada']);
});

it('entre misiones del mismo estado van primero las más nuevas', function () {
    $vieja = misionPara($this->e->unidad, ['titulo' => 'Vieja', 'lanza_at' => now()->subDays(2)]);
    $reciente = misionPara($this->e->unidad, ['titulo' => 'Reciente', 'lanza_at' => now()->subHour()]);

    expect(collect(app(MisionesDelParticipante::class)->lista($this->yo))->pluck('titulo')->all())->toBe(['Reciente', 'Vieja']);
});

// ── Talleres ────────────────────────────────────────────────────────────────

it('los talleres se ordenan: en curso, con lugar, apuntado y lleno', function () {
    $lleno = actividadPara($this->e->unidad, ['titulo' => 'A lleno']);
    Reserva::create(['turno_id' => turnoDe($lleno, '+2 hours', '+3 hours', cupo: 1)->id, 'user_id' => $this->otro->id]);

    $apuntado = actividadPara($this->e->unidad, ['titulo' => 'B apuntado']);
    Reserva::create(['turno_id' => turnoDe($apuntado, '+4 hours', '+5 hours', cupo: 5)->id, 'user_id' => $this->yo->id]);

    $conLugar = actividadPara($this->e->unidad, ['titulo' => 'C con lugar']);
    turnoDe($conLugar, '+6 hours', '+7 hours', cupo: 5);

    $enCurso = actividadPara($this->e->unidad, ['titulo' => 'D en curso']);
    turnoDe($enCurso, '-10 minutes', '+50 minutes', cupo: 5);

    expect(($this->titulos)(true))->toBe(['D en curso', 'C con lugar', 'B apuntado', 'A lleno']);
});

it('dentro de un taller: en curso, con lugar y lleno, por ese orden; y si ya estoy apuntado, mi turno va arriba', function () {
    $t = actividadPara($this->e->unidad, ['titulo' => 'Taller']);
    $lleno = turnoDe($t, '+1 hour', '+2 hours', cupo: 1);
    Reserva::create(['turno_id' => $lleno->id, 'user_id' => $this->otro->id]);
    $conLugar = turnoDe($t, '+3 hours', '+4 hours', cupo: 5);
    $enCurso = turnoDe($t, '-10 minutes', '+50 minutes', cupo: 5);

    $estados = fn () => collect(($this->agenda)()[0]['turnos'])->pluck('estado', 'id');
    expect($estados()->keys()->all())->toBe([$enCurso->id, $conLugar->id, $lleno->id])
        ->and($estados()->values()->all())->toBe(['en_curso', 'con_lugar', 'lleno']);

    app(Reservar::class)($this->yo, $conLugar);
    expect($estados()->keys()->first())->toBe($conLugar->id)->and($estados()[$conLugar->id])->toBe('apuntado');
});

// ── Estaciones ──────────────────────────────────────────────────────────────

it('de las estaciones solo se ven las disponibles; no las ocupadas por otro equipo ni las ya empezadas', function () {
    $libre = actividadPara($this->e->unidad, ['titulo' => 'Libre'], porEquipo: true);
    turnoDe($libre, '+2 hours', '+3 hours', cupo: 1);

    $otroEquipo = escenario(2);
    $ocupada = actividadPara($this->e->unidad, ['titulo' => 'Ocupada'], porEquipo: true);
    $turnoOcupado = turnoDe($ocupada, '+2 hours', '+3 hours', cupo: 1);
    Reserva::create(['turno_id' => $turnoOcupado->id, 'patrulla_id' => $otroEquipo->patrulla->id]);

    $empezada = actividadPara($this->e->unidad, ['titulo' => 'Empezada'], porEquipo: true);
    turnoDe($empezada, '-10 minutes', '+50 minutes', cupo: 1);

    expect(($this->titulos)(false))->toBe(['Libre']);

    $html = $this->actingAs($this->yo)->get(route('app.actividades'))->assertOk()->getContent();
    expect($html)->toContain('Libre')->not->toContain('Ocupada')->not->toContain('Empezada')->not->toContain('Ocupado por otro equipo');
});

it('si mi equipo ya reservó una estación, solo se ve esa: las otras (bloqueadas) no se muestran', function () {
    $mia = actividadPara($this->e->unidad, ['titulo' => 'Mía'], porEquipo: true);
    $otra = actividadPara($this->e->unidad, ['titulo' => 'Otra libre'], porEquipo: true);
    turnoDe($otra, '+5 hours', '+6 hours', cupo: 1);
    app(Reservar::class)($this->yo, turnoDe($mia, '+2 hours', '+3 hours', cupo: 1));

    $estaciones = collect(($this->agenda)())->where('taller', false);

    expect($estaciones->pluck('titulo')->all())->toBe(['Mía'])
        ->and($estaciones->first()['turnos'][0]['estado'])->toBe('reservado');
});

it('una estación reservada por mi equipo que ya está en curso se sigue viendo, como «en curso»', function () {
    $mia = actividadPara($this->e->unidad, ['titulo' => 'Mía'], porEquipo: true);
    Reserva::create(['turno_id' => turnoDe($mia, '-10 minutes', '+50 minutes', cupo: 1)->id, 'patrulla_id' => $this->e->patrulla->id]);

    $turno = collect(($this->agenda)())->firstWhere('titulo', 'Mía')['turnos'][0];

    expect($turno['estado'])->toBe('en_curso')->and($turno['reservado'])->toBeTrue();
});

it('las estaciones disponibles van por orden alfabético y su mejor turno decide su lugar', function () {
    foreach (['Zeta', 'Alfa'] as $nombre) {
        turnoDe(actividadPara($this->e->unidad, ['titulo' => $nombre], porEquipo: true), '+2 hours', '+3 hours', cupo: 1);
    }

    expect(($this->titulos)(false))->toBe(['Alfa', 'Zeta']);
});

// ── Pantalla ────────────────────────────────────────────────────────────────

it('la agenda separa claramente «Talleres» de «Estaciones JOTA», con los talleres primero', function () {
    turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Un taller']), '+1 hour', '+2 hours', cupo: 5);
    turnoDe(actividadPara($this->e->unidad, ['titulo' => 'Una estación'], porEquipo: true), '+3 hours', '+4 hours', cupo: 1);

    $html = $this->actingAs($this->yo)->get(route('app.actividades'))->assertOk()->getContent();

    expect(strpos($html, 'Talleres'))->toBeLessThan(strpos($html, 'Estaciones JOTA'))
        ->and(strpos($html, 'Un taller'))->toBeLessThan(strpos($html, 'Estaciones JOTA'))
        ->and(strpos($html, 'Estaciones JOTA'))->toBeLessThan(strpos($html, 'Una estación'));
});

it('si no hay estaciones disponibles o talleres, cada grupo lo dice', function () {
    $this->actingAs($this->yo)->get(route('app.actividades'))->assertOk()
        ->assertSee('Por ahora no hay talleres para tu unidad.')->assertSee('Por ahora no hay estaciones disponibles para tu equipo.');
});
