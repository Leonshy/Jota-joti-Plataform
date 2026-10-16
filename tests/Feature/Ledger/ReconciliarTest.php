<?php

use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Puntos;
use App\Models\Patrulla;
use App\Models\User;

it('no reporta diferencias cuando todo pasó por el ledger', function () {
    $user = User::factory()->create();
    $patrulla = Patrulla::factory()->create();
    app(Puntos::class)->individual($user, 40);
    app(Puntos::class)->patrulla($patrulla, 60);
    app(Jlis::class)->acreditar($user, 40);

    $this->artisan('jota:reconciliar')->assertSuccessful();
});

it('detecta un saldo cacheado que no coincide y lo corrige con --corregir', function () {
    $user = User::factory()->create();
    app(Jlis::class)->acreditar($user, 40);
    User::whereKey($user->id)->update(['jlis' => 999]);

    $this->artisan('jota:reconciliar')->assertFailed();
    $this->artisan('jota:reconciliar --corregir')->assertSuccessful();

    expect($user->fresh()->jlis)->toBe(40);
    $this->artisan('jota:reconciliar')->assertSuccessful();
});
