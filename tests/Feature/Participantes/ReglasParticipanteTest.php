<?php

use App\Domain\Participantes\ReglasParticipante;
use App\Enums\Rol;
use App\Models\Patrulla;

beforeEach(fn () => $this->s = estructuraImportacion());

function base(array $extra = []): array
{
    return array_merge(['ci' => '1234567', 'nombre' => 'Ana Benítez', 'rol' => Rol::Beneficiario], $extra);
}

it('normaliza el CI sacando puntos, guiones y espacios', function () {
    expect(ReglasParticipante::normalizarCi(' 1.234.567 '))->toBe('1234567')
        ->and(ReglasParticipante::normalizarCi('1 234-567'))->toBe('1234567');
});

it('un participante de Tropa con grupo, unidad y patrulla es válido', function () {
    $patrulla = Patrulla::factory()->create(['unidad_id' => $this->s->tropa->id, 'grupo_id' => $this->s->genes->id]);

    expect(ReglasParticipante::validar(base(['grupo_id' => $this->s->genes->id, 'unidad_id' => $this->s->tropa->id, 'patrulla_id' => $patrulla->id])))->toBe([]);
});

it('un rover con grupo y unidad, sin patrulla, es válido', function () {
    expect(ReglasParticipante::validar(base(['grupo_id' => $this->s->aquino->id, 'unidad_id' => $this->s->rovers->id])))->toBe([]);
});

it('los Rovers no tienen patrulla', function () {
    $e = ReglasParticipante::validar(base(['grupo_id' => $this->s->genes->id, 'unidad_id' => $this->s->rovers->id, 'patrulla_nueva' => true]));

    expect($e)->toHaveKey('patrulla')->and($e['patrulla'])->toContain('Rovers');
});

it('en Tropa cada participante va en una patrulla', function () {
    expect(ReglasParticipante::validar(base(['grupo_id' => $this->s->genes->id, 'unidad_id' => $this->s->tropa->id])))->toHaveKey('patrulla');
});

it('la patrulla debe ser de la misma unidad y del mismo grupo', function () {
    $deOtraUnidad = Patrulla::factory()->create(['unidad_id' => $this->s->rovers->id, 'grupo_id' => $this->s->genes->id]);
    $deOtroGrupo = Patrulla::factory()->create(['unidad_id' => $this->s->tropa->id, 'grupo_id' => $this->s->aquino->id]);
    $datos = base(['grupo_id' => $this->s->genes->id, 'unidad_id' => $this->s->tropa->id]);

    expect(ReglasParticipante::validar($datos + ['patrulla_id' => $deOtraUnidad->id]))->toHaveKey('patrulla')
        ->and(ReglasParticipante::validar($datos + ['patrulla_id' => $deOtroGrupo->id])['patrulla'])->toContain('otro grupo');
});

it('exige CI numérico de 4 a 12 dígitos y nombre', function (string $ci) {
    expect(ReglasParticipante::validar(base(['ci' => $ci, 'grupo_id' => $this->s->genes->id, 'unidad_id' => $this->s->rovers->id])))->toHaveKey('ci');
})->with(['', 'abc', '123', '1234567890123', '12a4567']);

it('exige nombre', function () {
    expect(ReglasParticipante::validar(base(['nombre' => '  ', 'grupo_id' => $this->s->genes->id, 'unidad_id' => $this->s->rovers->id])))->toHaveKey('nombre');
});

it('el beneficiario necesita grupo y unidad', function () {
    expect(ReglasParticipante::validar(base()))->toHaveKeys(['grupo', 'unidad']);
});

it('el dirigente necesita unidad pero no grupo ni patrulla', function () {
    expect(ReglasParticipante::validar(base(['rol' => Rol::Dirigente, 'unidad_id' => $this->s->tropa->id])))->toBe([])
        ->and(ReglasParticipante::validar(base(['rol' => Rol::Dirigente])))->toHaveKey('unidad')
        ->and(ReglasParticipante::validar(base(['rol' => Rol::Dirigente, 'unidad_id' => $this->s->tropa->id, 'patrulla_nueva' => true])))->toHaveKey('patrulla');
});

it('la tienda y el admin no necesitan unidad', function (Rol $rol) {
    expect(ReglasParticipante::validar(base(['rol' => $rol])))->toBe([]);
})->with([Rol::Tienda, Rol::Admin]);
