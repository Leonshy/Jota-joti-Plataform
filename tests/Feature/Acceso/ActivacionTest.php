<?php

use App\Domain\Acceso\ActivarCuenta;
use App\Domain\Acceso\CiNoRegistrado;
use App\Domain\Acceso\CuentaYaActivada;
use App\Domain\Acceso\LoginCiPin;
use App\Domain\Acceso\ResetearPin;
use App\Enums\Rol;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;

it('activa la cuenta con solo el CI y crea el PIN de 4 dígitos', function () {
    $user = User::factory()->sinActivar()->create();

    app(ActivarCuenta::class)($user->ci, '4321');

    $user->refresh();
    expect($user->activado_at)->not->toBeNull()
        ->and(Hash::check('4321', $user->pin_hash))->toBeTrue()
        ->and($user->pin_hash)->not->toBe('4321');
});

it('tolera espacios alrededor del CI', function () {
    $user = User::factory()->sinActivar()->create();

    app(ActivarCuenta::class)("  {$user->ci} ", '4321');

    expect($user->fresh()->activado_at)->not->toBeNull();
});

it('un CI que no está cargado no se puede activar', function () {
    app(ActivarCuenta::class)('0000000', '4321');
})->throws(CiNoRegistrado::class);

it('una cuenta que ya tiene PIN no se puede reactivar ni pisar', function () {
    $user = User::factory()->create(); // PIN 1234

    expect(fn () => app(ActivarCuenta::class)($user->ci, '9999'))->toThrow(CuentaYaActivada::class);
    expect(Hash::check('1234', $user->fresh()->pin_hash))->toBeTrue();
});

it('exige un PIN de exactamente 4 dígitos', function (string $pin) {
    $user = User::factory()->sinActivar()->create();

    app(ActivarCuenta::class)($user->ci, $pin);
})->with(['123', '12345', '123456', 'abcd', '12a4', ''])->throws(InvalidArgumentException::class);

it('el admin resetea el PIN: la persona vuelve a crearlo con solo su CI y se cierran sus sesiones', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();
    $user = User::factory()->create();
    DB::table('sessions')->insert(['id' => 's1', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

    app(ResetearPin::class)($admin, $user);

    $user->refresh();
    expect($user->pin_hash)->toBeNull()
        ->and($user->activado_at)->toBeNull()
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);

    app(ActivarCuenta::class)($user->ci, '5555');
    expect(app(LoginCiPin::class)($user->ci, '5555')->is($user))->toBeTrue();
});

it('solo el admin puede resetear un PIN', function () {
    $dirigente = User::factory()->rol(Rol::Dirigente)->create();

    app(ResetearPin::class)($dirigente, User::factory()->create());
})->throws(AuthorizationException::class);
