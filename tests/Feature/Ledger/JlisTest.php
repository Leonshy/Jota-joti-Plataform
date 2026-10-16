<?php

use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Puntos;
use App\Domain\Ledger\SaldoInsuficiente;
use App\Models\MovJli;
use App\Models\User;

it('acredita y debita LisCoins', function () {
    $user = User::factory()->create();

    app(Jlis::class)->acreditar($user, 100);
    app(Jlis::class)->debitar($user, 30, motivo: 'Cobro');

    expect($user->fresh()->jlis)->toBe(70);
});

it('nunca deja el saldo negativo y no escribe el movimiento', function () {
    $user = User::factory()->create();
    app(Jlis::class)->acreditar($user, 20);

    expect(fn () => app(Jlis::class)->debitar($user, 21))->toThrow(SaldoInsuficiente::class)
        ->and($user->fresh()->jlis)->toBe(20)
        ->and(MovJli::count())->toBe(1);
});

it('permite gastar exactamente todo el saldo', function () {
    $user = User::factory()->create();
    app(Jlis::class)->acreditar($user, 25);

    app(Jlis::class)->debitar($user, 25);

    expect($user->fresh()->jlis)->toBe(0);
});

it('exige cantidades positivas', function () {
    $user = User::factory()->create();

    expect(fn () => app(Jlis::class)->acreditar($user, 0))->toThrow(InvalidArgumentException::class)
        ->and(fn () => app(Jlis::class)->debitar($user, -5))->toThrow(InvalidArgumentException::class);
});

it('los puntos y los LisCoins son independientes: una penalización no toca los LisCoins', function () {
    $user = User::factory()->create();
    app(Jlis::class)->acreditar($user, 50);

    app(Puntos::class)->individual($user, -10, motivo: 'Penalización');

    expect($user->fresh()->jlis)->toBe(50)->and($user->fresh()->puntos)->toBe(-10);
});
