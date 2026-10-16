<?php

use App\Domain\Acceso\UnidadActiva;
use App\Enums\Rol;
use App\Livewire\Dirigente\Asistencia;
use App\Livewire\Dirigente\Cumplimientos;
use App\Livewire\Dirigente\Penalizaciones;
use App\Livewire\Dirigente\Revisar;
use App\Models\Entrega;
use App\Models\Reserva;
use App\Models\SesionAsistencia;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->a = escenario(2);
    $this->b = escenario(2);
    $this->admin = User::factory()->rol(Rol::Admin)->create();
});

it('el administrador entra a todas las pantallas del dirigente', function (string $ruta) {
    $this->actingAs($this->admin)->get(route($ruta))->assertOk();
})->with(['dirigente.inicio', 'dirigente.asistencia', 'dirigente.cumplimientos', 'dirigente.penalizaciones', 'dirigente.reservas', 'dirigente.ranking']);

it('elige la unidad sobre la que actúa y solo el administrador puede cambiarla', function () {
    $this->actingAs($this->admin)->post(route('dirigente.unidad'), ['unidad' => $this->b->unidad->id])->assertRedirect();
    expect(app(UnidadActiva::class)->para($this->admin))->toBe($this->b->unidad->id);

    $this->actingAs($this->a->dirigente)->post(route('dirigente.unidad'), ['unidad' => $this->b->unidad->id])->assertForbidden();
    expect(app(UnidadActiva::class)->para($this->a->dirigente))->toBe($this->a->unidad->id);
});

it('la cola, las reservas y las asistencias siguen la unidad elegida', function () {
    $mA = misionPara($this->a->unidad, ['titulo' => 'Mision A']);
    $mB = misionPara($this->b->unidad, ['titulo' => 'Mision B']);
    Entrega::factory()->create(['mision_id' => $mA->id, 'user_id' => $this->a->miembros[0]->id]);
    Entrega::factory()->create(['mision_id' => $mB->id, 'user_id' => $this->b->miembros[0]->id]);
    $actA = actividadPara($this->a->unidad, ['titulo' => 'Taller A']);
    $actB = actividadPara($this->b->unidad, ['titulo' => 'Taller B']);
    Reserva::create(['turno_id' => turnoDe($actB)->id, 'user_id' => $this->b->miembros[0]->id]);

    $this->actingAs($this->admin)->withSession(['unidad_activa' => $this->a->unidad->id]);
    $this->get(route('dirigente.inicio'))->assertSee('Mision A')->assertDontSee('Mision B');
    $this->get(route('dirigente.asistencia'))->assertSee('Taller A')->assertDontSee('Taller B');
    $this->get(route('dirigente.reservas'))->assertDontSee($this->b->miembros[0]->name);

    $this->withSession(['unidad_activa' => $this->b->unidad->id]);
    $this->get(route('dirigente.inicio'))->assertSee('Mision B')->assertDontSee('Mision A');
    $this->get(route('dirigente.reservas'))->assertSee($this->b->miembros[0]->name);
});

it('el administrador aprueba entregas, abre sesiones, marca cumplidas y penaliza', function () {
    $this->actingAs($this->admin)->withSession(['unidad_activa' => $this->b->unidad->id]);
    $yo = $this->b->miembros[0];

    $entrega = Entrega::factory()->create(['mision_id' => misionPara($this->b->unidad, ['puntos' => 40])->id, 'user_id' => $yo->id]);
    Livewire::test(Revisar::class, ['entrega' => $entrega])->call('aprobar')->assertSet('paso', 'aprobada');
    expect($yo->fresh()->puntos)->toBe(40);

    $act = actividadPara($this->b->unidad, ['titulo' => 'Radio']);
    Livewire::test(Asistencia::class)->call('abrir', $act->id);
    expect(SesionAsistencia::sole()->abierta_por)->toBe($this->admin->id);

    $equipo = actividadPara($this->b->unidad, ['puntos' => 30], porEquipo: true);
    Livewire::test(Cumplimientos::class, ['actividad' => $equipo])->call('pedirCumplida', 'p'.($this->b->patrulla->id))->call('cumplida');
    expect($this->b->patrulla->fresh()->puntos)->toBe(30);

    Livewire::test(Penalizaciones::class)->call('elegir', $yo->id)->set('cantidad', '5')->set('motivo', 'x')->call('aplicar')->assertHasNoErrors();
    expect($yo->fresh()->puntos)->toBe(35);
});

it('con una unidad elegida no se actúa sobre personas de otra unidad', function () {
    $this->actingAs($this->admin)->withSession(['unidad_activa' => $this->a->unidad->id]);

    Livewire::test(Penalizaciones::class)->call('elegir', $this->b->miembros[0]->id)->assertNotFound();
});

it('un participante y la tienda siguen sin entrar a las pantallas de dirigente', function () {
    $this->actingAs($this->a->miembros[0])->get(route('dirigente.inicio'))->assertForbidden();
    $this->actingAs(User::factory()->rol(Rol::Tienda)->create())->get(route('dirigente.inicio'))->assertForbidden();
});

it('un dirigente ve el mismo trabajo de siempre, sin selector de unidad', function () {
    $this->actingAs($this->a->dirigente)->get(route('dirigente.inicio'))->assertOk()->assertDontSee('selector-unidad', false);
    $this->actingAs($this->admin)->get(route('dirigente.inicio'))->assertSee('selector-unidad', false);
});
