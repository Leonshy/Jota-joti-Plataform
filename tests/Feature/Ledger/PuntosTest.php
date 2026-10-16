<?php

use App\Domain\Ledger\Puntos;
use App\Models\MovPuntoIndividual;
use App\Models\Patrulla;
use App\Models\User;

it('acredita puntos individuales y actualiza el saldo cacheado', function () {
    $user = User::factory()->create();
    $dirigente = User::factory()->create();

    $mov = app(Puntos::class)->individual($user, 30, motivo: 'Misión', registradoPor: $dirigente);
    app(Puntos::class)->individual($user, 20);

    expect($user->fresh()->puntos)->toBe(50)
        ->and($mov->registrado_por)->toBe($dirigente->id)
        ->and(MovPuntoIndividual::count())->toBe(2);
});

it('acredita puntos de patrulla', function () {
    $patrulla = Patrulla::factory()->create();

    app(Puntos::class)->patrulla($patrulla, 50);

    expect($patrulla->fresh()->puntos)->toBe(50);
});

it('permite puntos negativos por penalización (D14)', function () {
    $user = User::factory()->create();

    app(Puntos::class)->individual($user, -15, motivo: 'Penalización');

    expect($user->fresh()->puntos)->toBe(-15);
});

it('rechaza movimientos de cantidad cero', function () {
    app(Puntos::class)->individual(User::factory()->create(), 0);
})->throws(InvalidArgumentException::class);

it('no deja editar ni borrar movimientos', function () {
    $mov = app(Puntos::class)->individual(User::factory()->create(), 10);

    expect(fn () => $mov->update(['cantidad' => 99]))->toThrow(LogicException::class)
        ->and(fn () => $mov->delete())->toThrow(LogicException::class);
});

it('revierte el movimiento si falla la transacción externa', function () {
    $user = User::factory()->create();

    try {
        DB::transaction(function () use ($user) {
            app(Puntos::class)->individual($user, 40);
            throw new RuntimeException('falla posterior');
        });
    } catch (RuntimeException) {
    }

    expect($user->fresh()->puntos)->toBe(0)->and(MovPuntoIndividual::count())->toBe(0);
});
