<?php

use App\Enums\Rol;
use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->tropa = Unidad::factory()->create();
    $this->caminantes = Unidad::factory()->create();
    $this->patrullaTropa = Patrulla::factory()->create(['unidad_id' => $this->tropa->id]);
    $this->dirigenteTropa = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => $this->tropa->id]);
    $this->dirigenteCaminantes = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => $this->caminantes->id]);
    $this->admin = User::factory()->rol(Rol::Admin)->create();
});

it('el dirigente gestiona su unidad y su patrulla', function () {
    expect(Gate::forUser($this->dirigenteTropa)->allows('gestionar', $this->tropa))->toBeTrue()
        ->and(Gate::forUser($this->dirigenteTropa)->allows('penalizar', $this->patrullaTropa))->toBeTrue();
});

it('el dirigente de otra unidad recibe 403', function () {
    $intento = fn () => Gate::forUser($this->dirigenteCaminantes)->authorize('gestionar', $this->tropa);

    expect($intento)->toThrow(AuthorizationException::class);
    expect(Gate::forUser($this->dirigenteCaminantes)->inspect('penalizar', $this->patrullaTropa)->denied())->toBeTrue();
});

it('por HTTP, el dirigente de otra unidad recibe 403 y el de la unidad 200', function () {
    Route::middleware(['web', 'can:gestionar,unidad'])->get('/_prueba/unidades/{unidad}', fn (Unidad $unidad) => 'ok');

    $this->actingAs($this->dirigenteCaminantes)->get("/_prueba/unidades/{$this->tropa->id}")->assertForbidden();
    $this->actingAs($this->dirigenteTropa)->get("/_prueba/unidades/{$this->tropa->id}")->assertOk();
    $this->actingAs($this->admin)->get("/_prueba/unidades/{$this->tropa->id}")->assertOk();
});

it('el admin gestiona todas las unidades', function () {
    expect(Gate::forUser($this->admin)->allows('gestionar', $this->tropa))->toBeTrue()
        ->and(Gate::forUser($this->admin)->allows('gestionar', $this->caminantes))->toBeTrue();
});

it('beneficiario y tienda no gestionan ninguna unidad, ni la propia', function (Rol $rol) {
    $user = User::factory()->rol($rol)->create(['unidad_id' => $this->tropa->id]);

    expect(Gate::forUser($user)->denies('gestionar', $this->tropa))->toBeTrue();
})->with([Rol::Beneficiario, Rol::Tienda]);

it('un dirigente sin unidad no gestiona nada', function () {
    $user = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => null]);

    expect(Gate::forUser($user)->denies('gestionar', $this->tropa))->toBeTrue();
});

it('solo el admin resetea PIN', function () {
    $objetivo = User::factory()->create(['unidad_id' => $this->tropa->id]);

    expect(Gate::forUser($this->admin)->allows('resetearPin', $objetivo))->toBeTrue()
        ->and(Gate::forUser($this->dirigenteTropa)->denies('resetearPin', $objetivo))->toBeTrue();
});

it('el dirigente ve a personas de su unidad y no de otras', function () {
    $propio = User::factory()->create(['unidad_id' => $this->tropa->id]);
    $ajeno = User::factory()->create(['unidad_id' => $this->caminantes->id]);

    expect(Gate::forUser($this->dirigenteTropa)->allows('view', $propio))->toBeTrue()
        ->and(Gate::forUser($this->dirigenteTropa)->denies('view', $ajeno))->toBeTrue();
});
