<?php

use App\Domain\Inicio\ResumenParticipante;
use App\Domain\Misiones\MisionesDelParticipante;
use App\Enums\Modalidad;
use App\Filament\Resources\Actividades\Pages\CreateActividad;
use App\Filament\Resources\Misiones\Pages\CreateMision;
use App\Models\Reserva;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->e = escenario(2);
    $this->yo = $this->e->miembros[0];
});

it('la agenda muestra el lugar de cada actividad', function () {
    $a = actividadPara($this->e->unidad, ['titulo' => 'Taller de nudos', 'lugar' => 'Salón grande']);
    turnoDe($a, '+1 hour', '+2 hours', cupo: 5);

    $this->actingAs($this->yo)->get(route('app.actividades'))->assertOk()
        ->assertSee('Taller de nudos')->assertSee('Salón grande');
});

it('mis reservas y el inicio muestran el lugar de la actividad reservada', function () {
    $a = actividadPara($this->e->unidad, ['titulo' => 'Postas', 'lugar' => 'Cancha de fútbol']);
    Reserva::create(['turno_id' => turnoDe($a, '+1 hour', '+2 hours', cupo: 5)->id, 'user_id' => $this->yo->id]);

    $this->actingAs($this->yo)->get(route('app.reservas'))->assertOk()->assertSee('Cancha de fútbol');
    $this->actingAs($this->yo)->get(route('app.inicio'))->assertOk()->assertSee('Cancha de fútbol');
    expect(app(ResumenParticipante::class)($this->yo)['proximo_turno']['lugar'])->toBe('Cancha de fútbol');
});

it('la lista y el detalle de una misión muestran el lugar', function () {
    $m = misionPara($this->e->unidad, ['titulo' => 'Mensaje en clave', 'lugar' => 'Carpa de dirigentes']);

    expect(app(MisionesDelParticipante::class)->lista($this->yo)[0]['lugar'])->toBe('Carpa de dirigentes');
    $this->actingAs($this->yo)->get(route('app.misiones'))->assertOk()->assertSee('Carpa de dirigentes');
    $this->actingAs($this->yo)->get(route('app.mision', $m))->assertOk()->assertSee('Carpa de dirigentes');
});

it('lo ya cargado sin lugar sigue funcionando y no muestra un lugar vacío', function () {
    $a = actividadPara($this->e->unidad, ['titulo' => 'Sin lugar', 'lugar' => null]);
    turnoDe($a, '+1 hour', '+2 hours', cupo: 5);
    misionPara($this->e->unidad, ['titulo' => 'Misión sin lugar', 'lugar' => null]);

    $this->actingAs($this->yo)->get(route('app.actividades'))->assertOk()->assertSee('Sin lugar')->assertDontSee('Lugar:');
    $this->actingAs($this->yo)->get(route('app.misiones'))->assertOk()->assertSee('Misión sin lugar');
});

it('el panel pide el lugar al crear una actividad y una misión', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(adminDePrueba());

    Livewire::test(CreateActividad::class)
        ->fillForm(['titulo' => 'Taller', 'tipo' => 'taller', 'modalidad' => Modalidad::Individual->value, 'puntos' => 10, 'unidades' => [$this->e->unidad->id]])
        ->call('create')->assertHasFormErrors(['lugar' => 'required']);

    Livewire::test(CreateMision::class)
        ->fillForm([
            'titulo' => 'Misión', 'descripcion' => 'Hacer algo.', 'modalidad' => Modalidad::Individual->value, 'puntos' => 10,
            'unidades' => [$this->e->unidad->id], 'lanza_at' => now()->addHour(),
        ])
        ->call('create')->assertHasFormErrors(['lugar' => 'required']);
});
