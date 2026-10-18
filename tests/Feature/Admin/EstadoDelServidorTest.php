<?php

use App\Filament\Pages\EstadoDelServidor;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

it('el estado del servidor es solo del administrador', function () {
    $this->actingAs(escenario()->dirigente);
    expect(EstadoDelServidor::canAccess())->toBeFalse();

    $this->actingAs(adminDePrueba());
    expect(EstadoDelServidor::canAccess())->toBeTrue();
});

it('muestra los límites de subida, la memoria, las extensiones, la hora y la cola', function () {
    $this->actingAs(adminDePrueba());

    $nombres = collect(Livewire::test(EstadoDelServidor::class)->instance()->revisiones())->pluck('nombre')->implode(' | ');

    expect($nombres)->toContain('upload_max_filesize')->toContain('post_max_size')->toContain('memory_limit')
        ->toContain('GD')->toContain('Hora del servidor')->toContain('cola');

    $this->get('/admin/estado-del-servidor')->assertOk()->assertSee('Estado del servidor')->assertSee('Lo que ve la');
});

it('los errores de subida salen en español y no como un código interno', function () {
    foreach (['validation.uploaded', 'validation.max.file', 'validation.required'] as $clave) {
        expect(__($clave, ['attribute' => 'foto', 'max' => 8192]))->not->toBe($clave);
    }

    expect(__('validation.uploaded'))->toContain('No se pudo subir');
});

it('las pantallas con fotos achican la imagen en el celular antes de subirla, sin wire:model en el input', function () {
    $e = escenario();
    $mision = misionPara($e->unidad);

    $html = $this->actingAs($e->miembros[0])->get(route('app.entregar', $mision))->assertOk()->getContent();

    expect($html)->toContain('jotaFotoLiviana')->toContain("\$wire.upload('nueva'")->not->toContain('wire:model="nueva"');

    $html = $this->actingAs($e->miembros[0])->get(route('app.contactos.nuevo', 'joti'))->assertOk()->getContent();
    expect($html)->toContain('jotaFotoLiviana')->toContain("\$wire.upload('captura'")->not->toContain('wire:model="captura"');
});

it('el sitio trae los límites de subida en public/.user.ini', function () {
    $ini = file_get_contents(public_path('.user.ini'));

    expect($ini)->toContain('upload_max_filesize = 12M')->toContain('post_max_size = 24M');
});
