<?php

use App\Domain\Acceso\CiNoRegistrado;
use App\Domain\Acceso\CuentaSinActivar;
use App\Domain\Acceso\LoginCiPin;
use App\Domain\Acceso\PinIncorrecto;
use App\Models\User;

it('inicia con CI + PIN correctos', function () {
    $user = User::factory()->create();

    expect(app(LoginCiPin::class)($user->ci, '1234')->is($user))->toBeTrue();
});

it('distingue CI no cargado, cuenta sin PIN y PIN incorrecto', function () {
    $user = User::factory()->create();
    $sinActivar = User::factory()->sinActivar()->create();
    $login = app(LoginCiPin::class);

    expect(fn () => $login('0000000', '1234'))->toThrow(CiNoRegistrado::class)
        ->and(fn () => $login($sinActivar->ci, '1234'))->toThrow(CuentaSinActivar::class)
        ->and(fn () => $login($user->ci, '0000'))->toThrow(PinIncorrecto::class);
});

it('no bloquea la cuenta por equivocarse: se puede seguir intentando sin límite', function () {
    $user = User::factory()->create();
    $login = app(LoginCiPin::class);

    foreach (range(1, 50) as $_) {
        expect(fn () => $login($user->ci, '0000'))->toThrow(PinIncorrecto::class);
    }

    expect($login($user->ci, '1234')->is($user))->toBeTrue();
});

it('no limita por IP: muchos intentos seguidos desde la misma conexión siguen respondiendo', function () {
    $login = app(LoginCiPin::class);

    foreach (range(1, 400) as $_) {
        expect(fn () => $login('0000000', '1234'))->toThrow(CiNoRegistrado::class);
    }
});
