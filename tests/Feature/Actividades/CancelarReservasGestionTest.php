<?php

use App\Domain\Actividades\CancelarReserva;
use App\Domain\Actividades\TurnoYaComenzo;
use App\Enums\Rol;
use App\Filament\Resources\Reservas\Pages\ListReservas;
use App\Livewire\Dirigente\Reservas as ReservasDirigente;
use App\Models\Reserva;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->e = escenario(3);
    $this->yo = $this->e->miembros[0];
    $this->actividad = actividadPara($this->e->unidad, ['titulo' => 'Nudos']);
    $this->turno = turnoDe($this->actividad, '+1 hour', '+2 hours', cupo: 1);
    $this->reserva = Reserva::create(['turno_id' => $this->turno->id, 'user_id' => $this->yo->id]);
    $this->admin = User::factory()->rol(Rol::Admin)->create();
});

it('el dirigente de la unidad cancela la reserva de una persona y de una patrulla, y libera el cupo', function () {
    $porEquipo = turnoDe(actividadPara($this->e->unidad, [], porEquipo: true));
    $deLaPatrulla = Reserva::create(['turno_id' => $porEquipo->id, 'patrulla_id' => $this->e->patrulla->id]);

    app(CancelarReserva::class)($this->e->dirigente, $this->reserva);
    app(CancelarReserva::class)($this->e->dirigente, $deLaPatrulla);

    expect(Reserva::count())->toBe(0);
    // el lugar quedó libre: otra persona del turno con cupo 1 puede reservar
    expect($this->turno->reservas()->count())->toBe(0);
});

it('el dirigente de otra unidad no puede cancelar', function () {
    $otro = escenario(1)->dirigente;

    expect(fn () => app(CancelarReserva::class)($otro, $this->reserva))->toThrow(AuthorizationException::class);
    expect(Reserva::count())->toBe(1);
});

it('el admin y el dirigente pueden cancelar aunque el turno ya haya empezado; la persona no', function () {
    $this->travel(90)->minutes();

    expect(fn () => app(CancelarReserva::class)($this->yo, $this->reserva))->toThrow(TurnoYaComenzo::class);

    app(CancelarReserva::class)($this->e->dirigente, $this->reserva);
    expect(Reserva::count())->toBe(0);
});

it('el admin cancela desde el panel y el cupo se libera', function () {
    Livewire::actingAs($this->admin)->test(ListReservas::class)
        ->assertCanSeeTableRecords([$this->reserva])
        ->callAction(TestAction::make('cancelar')->table($this->reserva));

    expect(Reserva::count())->toBe(0);
});

it('solo el admin entra al panel de reservas', function () {
    $this->actingAs($this->e->dirigente)->get('/admin/reservas')->assertForbidden();
});

// ── Pantalla móvil del dirigente ────────────────────────────────────────────

it('el dirigente ve solo las reservas de su unidad y las vigentes', function () {
    $ajena = escenario(1);
    $otraAct = actividadPara($ajena->unidad, ['titulo' => 'Actividad ajena']);
    Reserva::create(['turno_id' => turnoDe($otraAct)->id, 'user_id' => $ajena->miembros[0]->id]);
    Reserva::create(['turno_id' => turnoDe($this->actividad, '-3 hours', '-2 hours')->id, 'user_id' => $this->e->miembros[1]->id]); // ya terminó

    $this->actingAs($this->e->dirigente)->get(route('dirigente.reservas'))->assertOk()
        ->assertSee('Nudos')->assertSee($this->yo->name)
        ->assertDontSee('Actividad ajena')->assertDontSee($ajena->miembros[0]->name)->assertDontSee($this->e->miembros[1]->name);
});

it('el dirigente busca por nombre y cancela con confirmación', function () {
    $c = Livewire::actingAs($this->e->dirigente)->test(ReservasDirigente::class)
        ->set('buscar', 'zzzzz')->assertSee(__('jota.dirigente.reservas_sin_resultados'))
        ->set('buscar', '')->assertSee($this->yo->name);

    $c->call('pedirCancelar', $this->reserva->id)->assertSee(__('jota.dirigente.reservas_cancelar_pregunta', ['quien' => $this->yo->name]));
    expect(Reserva::count())->toBe(1);

    $c->call('cancelar')->assertSet('confirmar', null);
    expect(Reserva::count())->toBe(0);
});

it('el dirigente no puede cancelar una reserva de otra unidad forzando el id', function () {
    $ajena = escenario(1);
    $r = Reserva::create(['turno_id' => turnoDe(actividadPara($ajena->unidad))->id, 'user_id' => $ajena->miembros[0]->id]);

    Livewire::actingAs($this->e->dirigente)->test(ReservasDirigente::class)->set('confirmar', $r->id)->call('cancelar');

    expect(Reserva::count())->toBe(2);
});

it('un participante no entra a las reservas del dirigente', function () {
    $this->actingAs($this->yo)->get(route('dirigente.reservas'))->assertForbidden();
});
