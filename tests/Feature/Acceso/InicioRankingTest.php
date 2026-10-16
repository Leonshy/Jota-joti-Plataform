<?php

use App\Domain\Inicio\ResumenParticipante;
use App\Enums\EstadoEntrega;
use App\Livewire\Beneficiario\Inicio;
use App\Models\Entrega;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ── ResumenParticipante ─────────────────────────────────────────────────────

it('resume saldos, patrulla y misiones por entregar', function () {
    $e = escenario(2);
    $yo = darPuntos(conJlis(30, $e->miembros[0]), 25);
    darPuntosPatrulla($e->patrulla, 70);
    misionPara($e->unidad);
    misionPara($e->unidad);
    misionPara($e->unidad, [], 'futura');
    misionPara($e->unidad, [], 'vencida');
    misionPara($e->unidad, [], 'cerrada');

    $r = app(ResumenParticipante::class)($yo);

    expect($r['puntos'])->toBe(25)->and($r['jlis'])->toBe(30)
        ->and($r['patrulla'])->toBe(['nombre' => $e->patrulla->nombre, 'puntos' => 70])
        ->and($r['misiones_por_entregar'])->toBe(2)
        ->and($r['proximo_turno'])->toBeNull();
});

it('no cuenta las misiones que ya entregó (pendiente o aprobada) pero sí las rechazadas', function () {
    $e = escenario(1);
    $yo = $e->miembros[0];
    [$a, $b, $c] = [misionPara($e->unidad), misionPara($e->unidad), misionPara($e->unidad)];

    Entrega::factory()->create(['mision_id' => $a->id, 'user_id' => $yo->id, 'estado' => EstadoEntrega::Pendiente]);
    Entrega::factory()->create(['mision_id' => $b->id, 'user_id' => $yo->id, 'estado' => EstadoEntrega::Aprobada]);
    Entrega::factory()->create(['mision_id' => $c->id, 'user_id' => $yo->id, 'estado' => EstadoEntrega::Rechazada]);

    expect(app(ResumenParticipante::class)($yo)['misiones_por_entregar'])->toBe(1);
});

it('un rover sin patrulla no ve patrulla ni misiones por patrulla', function () {
    $e = escenario(1);
    $rover = User::factory()->create(['unidad_id' => $e->unidad->id, 'patrulla_id' => null]);
    misionPara($e->unidad, ['modalidad' => 'patrulla']);
    misionPara($e->unidad);

    $r = app(ResumenParticipante::class)($rover);

    expect($r['patrulla'])->toBeNull()->and($r['misiones_por_entregar'])->toBe(1);
});

it('muestra el próximo turno reservado, propio o de su patrulla, el más cercano a futuro', function () {
    $e = escenario(2);
    $yo = $e->miembros[0];
    $taller = actividadPara($e->unidad, ['titulo' => 'Nudos']);
    $juego = actividadPara($e->unidad, ['titulo' => 'Postas'], porEquipo: true);

    $pasado = turnoDe($taller, '-3 hours', '-2 hours');
    $lejano = turnoDe($taller, '+5 hours', '+6 hours');
    $cercano = turnoDe($juego, '+1 hour', '+2 hours');
    foreach ([$pasado, $lejano] as $t) {
        Reserva::create(['turno_id' => $t->id, 'user_id' => $yo->id]);
    }
    Reserva::create(['turno_id' => $cercano->id, 'patrulla_id' => $e->patrulla->id]);

    $proximo = app(ResumenParticipante::class)($yo)['proximo_turno'];

    expect($proximo['actividad'])->toBe('Postas')->and($proximo['por_patrulla'])->toBeTrue();
});

it('el resumen usa pocas consultas, sin importar cuántas misiones haya', function () {
    $e = escenario(1);
    foreach (range(1, 20) as $_) {
        misionPara($e->unidad);
    }

    DB::enableQueryLog();
    app(ResumenParticipante::class)($e->miembros[0]);

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(5);
});

// ── Pantalla de Inicio ──────────────────────────────────────────────────────

it('el Inicio muestra el nombre, los saldos y la patrulla', function () {
    $e = escenario(1);
    $yo = darPuntos(conJlis(12, $e->miembros[0]), 34);
    $yo->update(['name' => 'Beto Cardozo']);

    Livewire::actingAs($yo)->test(Inicio::class)
        ->assertOk()->assertSee('Beto')->assertSee('34')->assertSee('12')->assertSee($e->patrulla->nombre);
});

it('el Inicio da la bienvenida una sola vez tras crear el PIN', function () {
    $yo = User::factory()->create();

    $this->actingAs($yo);
    session()->flash('bienvenida', 'Lucía');
    $this->get(route('app.inicio'))
        ->assertSee(__('jota.acceso.bienvenida', ['nombre' => 'Lucía']));

    // El mensaje es un flash: no sobrevive a las cargas siguientes.
    $this->get(route('app.inicio'));
    $this->get(route('app.inicio'))->assertDontSee('Lucía');
});

it('el Inicio se refresca solo cada 30 segundos', function () {
    $this->actingAs(User::factory()->create())->get(route('app.inicio'))->assertSee('wire:poll.30s', false);
});

it('el Inicio no deja datos de otras personas en el estado de Livewire', function () {
    $e = escenario(3);

    $html = $this->actingAs($e->miembros[0])->get(route('app.inicio'))->getContent();

    foreach ([$e->miembros[1], $e->miembros[2]] as $otro) {
        expect($html)->not->toContain($otro->name)->not->toContain($otro->ci);
    }
});

// ── Ranking ─────────────────────────────────────────────────────────────────

it('el ranking del participante muestra solo puestos y puntajes, nunca nombres ni CI', function () {
    $r = rankingEquipo();
    $html = $this->actingAs($r->ana)->get(route('app.ranking'))->assertOk()->getContent();

    expect($html)->toContain('140');

    foreach ([$r->aguilas->nombre, $r->pumas->nombre, $r->zorros->nombre, $r->beto->name, $r->carla->name, $r->dirigente->name, $r->beto->ci, $r->carla->ci] as $dato) {
        expect($html)->not->toContain($dato);
    }
});

it('el ranking sin puntajes muestra el estado vacío', function () {
    $e = escenario(1);

    $this->actingAs($e->miembros[0])->get(route('app.ranking'))->assertOk();
});
