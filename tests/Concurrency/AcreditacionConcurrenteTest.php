<?php

use App\Domain\Ledger\Jlis;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\User;
use Illuminate\Support\Facades\Concurrency;

it('10 acreditaciones simultáneas dejan el saldo exacto', function () {
    $userId = User::factory()->create()->id;

    $tareas = array_map(fn (int $i) => tareaAcreditarPuntos($userId, 10 + $i), range(1, 10));

    Concurrency::driver('process')->run($tareas);

    // 11 + 12 + ... + 20 = 155
    expect(User::find($userId)->puntos)->toBe(155)
        ->and(MovPuntoIndividual::where('user_id', $userId)->count())->toBe(10)
        ->and((int) MovPuntoIndividual::where('user_id', $userId)->sum('cantidad'))->toBe(155);
});

it('débitos simultáneos de LisCoins nunca dejan el saldo negativo', function () {
    $user = User::factory()->create();
    app(Jlis::class)->acreditar($user, 100);

    // 10 intentos de gastar 30 con 100 disponibles: pasan exactamente 3.
    $tareas = array_map(fn () => tareaDebitarJlis($user->id, 30), range(1, 10));

    $resultados = Concurrency::driver('process')->run($tareas);

    expect(array_count_values($resultados)['ok'])->toBe(3)
        ->and(User::find($user->id)->jlis)->toBe(10)
        ->and((int) MovJli::where('user_id', $user->id)->sum('cantidad'))->toBe(10);
});
