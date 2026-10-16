<?php

use App\Livewire\Dirigente\Penalizaciones;
use App\Models\Penalizacion;
use Livewire\Livewire;

beforeEach(function () {
    $this->e = escenario(2);
    $this->yo = darPuntos($this->e->miembros[0], 30);
    $this->dirigente = $this->e->dirigente;
});

it('busca a una persona de su unidad, la elige y la penaliza', function () {
    $yo = $this->yo;
    $yo->update(['name' => 'Lucía Benítez']);
    $ajena = escenario(1)->miembros[0];
    $ajena->update(['name' => 'Lucía Ajena']);

    $c = Livewire::actingAs($this->dirigente)->test(Penalizaciones::class)
        ->set('buscar', 'Lucía')->assertSee('Lucía Benítez')->assertDontSee('Lucía Ajena');

    $c->call('elegir', $yo->id)->set('cantidad', '5')->set('motivo', 'Llegó tarde')->call('aplicar')->assertHasNoErrors()
        ->assertSee(__('jota.dirigente.penalizada_titulo'))->assertSee('Llegó tarde');

    expect($yo->fresh())->puntos->toBe(25)->jlis->toBe(0)
        ->and(Penalizacion::sole()->registrado_por)->toBe($this->dirigente->id);
});

it('no se puede elegir a una persona de otra unidad forzando el id', function () {
    $ajena = escenario(1)->miembros[0];

    Livewire::actingAs($this->dirigente)->test(Penalizaciones::class)->call('elegir', $ajena->id)->assertNotFound();
});

it('valida persona, cantidad y motivo', function () {
    $c = Livewire::actingAs($this->dirigente)->test(Penalizaciones::class);

    $c->call('aplicar')->assertHasErrors(['buscar']);
    $c->call('elegir', $this->yo->id)->set('cantidad', '0')->set('motivo', 'x')->call('aplicar')->assertHasErrors(['cantidad']);
    $c->set('cantidad', '-3')->call('aplicar')->assertHasErrors(['cantidad']);
    $c->set('cantidad', '5')->set('motivo', '  ')->call('aplicar')->assertHasErrors(['motivo']);

    expect(Penalizacion::count())->toBe(0)->and($this->yo->fresh()->puntos)->toBe(30);
});

it('penaliza a una patrulla de su unidad y deja los puntos negativos si hace falta', function () {
    Livewire::actingAs($this->dirigente)->test(Penalizaciones::class)
        ->set('tipo', 'patrulla')->set('patrullaId', (string) $this->e->patrulla->id)
        ->set('cantidad', '40')->set('motivo', 'Desorden')->call('aplicar')->assertHasNoErrors();

    expect($this->e->patrulla->fresh()->puntos)->toBe(-40);
});

it('no penaliza una patrulla de otra unidad', function () {
    $ajena = escenario(1)->patrulla;

    Livewire::actingAs($this->dirigente)->test(Penalizaciones::class)
        ->set('tipo', 'patrulla')->set('patrullaId', (string) $ajena->id)->set('cantidad', '5')->set('motivo', 'x')->call('aplicar')->assertNotFound();
});

it('anula una penalización devolviendo los puntos, una sola vez y con motivo', function () {
    $c = Livewire::actingAs($this->dirigente)->test(Penalizaciones::class)
        ->call('elegir', $this->yo->id)->set('cantidad', '10')->set('motivo', 'Error')->call('aplicar');
    $p = Penalizacion::sole();
    expect($this->yo->fresh()->puntos)->toBe(20);

    $c->call('pedirAnular', $p->id)->call('anular')->assertHasErrors(['motivoAnulacion']);
    expect($this->yo->fresh()->puntos)->toBe(20);

    $c->set('motivoAnulacion', 'Me equivoqué de persona')->call('anular')->assertHasNoErrors()->assertSee(__('jota.dirigente.anulada'));
    expect($this->yo->fresh()->puntos)->toBe(30);

    $c->call('pedirAnular', $p->id)->set('motivoAnulacion', 'otra vez')->call('anular');
    expect($this->yo->fresh()->puntos)->toBe(30);
});

it('un dirigente no anula la penalización de otra unidad', function () {
    $otra = escenario(1);
    $p = Penalizacion::create(['user_id' => $otra->miembros[0]->id, 'cantidad' => 5, 'motivo' => 'x', 'registrado_por' => $otra->dirigente->id]);

    Livewire::actingAs($this->dirigente)->test(Penalizaciones::class)->set('anulando', $p->id)->set('motivoAnulacion', 'x')->call('anular');

    expect($p->fresh()->estaAnulada())->toBeFalse();
});

it('los participantes no entran a penalizar', function () {
    $this->actingAs($this->yo)->get(route('dirigente.penalizaciones'))->assertForbidden();
});
