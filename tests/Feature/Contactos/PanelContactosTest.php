<?php

use App\Domain\Contactos\Puntaje;
use App\Enums\TipoContacto;
use App\Filament\Pages\PuntajeContactos;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

it('el admin define los cuatro puntajes de los contactos desde el panel', function () {
    $this->actingAs(adminDePrueba());

    Livewire::test(PuntajeContactos::class)
        ->fillForm(['jota_local' => 10, 'jota_exterior' => 30, 'joti_local' => 5, 'joti_exterior' => 15])
        ->call('save')->assertHasNoFormErrors();

    $p = app(Puntaje::class);
    expect($p->para(TipoContacto::Jota, true))->toBe(10)->and($p->para(TipoContacto::Jota, false))->toBe(30)
        ->and($p->para(TipoContacto::Joti, true))->toBe(5)->and($p->para(TipoContacto::Joti, false))->toBe(15);
});

it('el panel muestra lo ya guardado y pide los cuatro puntajes, enteros y de 0 a 100.000', function () {
    $this->actingAs($admin = adminDePrueba());
    app(Puntaje::class)->guardar(['jota_local' => 1, 'jota_exterior' => 2, 'joti_local' => 3, 'joti_exterior' => 4], $admin);

    Livewire::test(PuntajeContactos::class)->assertFormSet(['jota_local' => 1, 'joti_exterior' => 4])
        ->fillForm(['joti_local' => null])->call('save')->assertHasFormErrors(['joti_local' => 'required'])
        ->fillForm(['joti_local' => -5])->call('save')->assertHasFormErrors(['joti_local'])
        ->fillForm(['joti_local' => 100001])->call('save')->assertHasFormErrors(['joti_local']);

    expect(app(Puntaje::class)->para(TipoContacto::Joti, true))->toBe(3);
});

it('solo el admin entra a la página de puntajes', function () {
    $e = escenario();

    $this->actingAs($e->dirigente);
    expect(PuntajeContactos::canAccess())->toBeFalse();

    $this->actingAs(adminDePrueba());
    expect(PuntajeContactos::canAccess())->toBeTrue();
});
