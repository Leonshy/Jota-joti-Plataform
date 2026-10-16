<?php

use App\Domain\Acceso\LoginCiPin;
use App\Domain\Acceso\PinIncorrecto;
use App\Domain\Actividades\TokenRotativo;
use App\Domain\Tienda\CrearCobro;
use App\Enums\Rol;
use App\Livewire\Acceso\Ingresar;
use App\Livewire\Dirigente\Penalizaciones;
use App\Livewire\Tienda\Devolver;
use App\Models\Entrega;
use App\Models\SesionAsistencia;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

// ── Límites de uso: por persona, nunca por IP ───────────────────────────────

it('escanear tiene tope por persona y no frena a otra persona de la misma red', function () {
    $e = escenario(2);
    $sesion = SesionAsistencia::create(['actividad_id' => actividadPara($e->unidad)->id, 'abierta_por' => $e->dirigente->id, 'secreto' => str_repeat('a', 64), 'abierta_at' => now()]);
    $url = route('asistencia.registrar', ['sesion' => $sesion, 'token' => str_repeat('0', 32)]);

    RateLimiter::clear('escaneos');
    $this->actingAs($e->miembros[0]);
    foreach (range(1, 40) as $_) {
        expect($this->get($url)->status())->not->toBe(429);
    }
    $this->get($url)->assertStatus(429);

    // El compañero, desde la misma conexión, sigue pudiendo escanear.
    expect($this->actingAs($e->miembros[1])->get($url)->status())->not->toBe(429);
});

it('el ingreso con CI y PIN NO tiene límite de intentos (decisión de producto)', function () {
    $user = User::factory()->create();
    $c = Livewire::test(Ingresar::class)->set('ci', $user->ci);

    foreach (range(1, 60) as $_) {
        $c->set('pin', '0000')->call('ingresar')->assertHasErrors(['pin'])->assertStatus(200);
    }

    $c->set('pin', '1234')->call('ingresar')->assertRedirect(route('app.inicio'));
});

it('pagar y ver un cobro tienen tope por persona', function () {
    $tienda = usuarioTienda();
    $yo = conJlis(10_000);
    $cobro = app(CrearCobro::class)($tienda, 1);

    RateLimiter::clear('pagos');
    $this->actingAs($yo);
    foreach (range(1, 40) as $_) {
        expect($this->getJson(route('cobro.ver', $cobro->token))->status())->not->toBe(429);
    }
    $this->getJson(route('cobro.ver', $cobro->token))->assertStatus(429);
});

// ── Tokens ──────────────────────────────────────────────────────────────────

it('el token del QR de una sesión no sirve en otra ni se puede adivinar cambiando un carácter', function () {
    $e = escenario(1);
    $act = actividadPara($e->unidad);
    $mk = fn () => SesionAsistencia::create(['actividad_id' => $act->id, 'abierta_por' => $e->dirigente->id, 'secreto' => bin2hex(random_bytes(32)), 'abierta_at' => now(), 'cerrada_at' => now()]);
    [$a, $b] = [$mk(), $mk()];
    $tokens = app(TokenRotativo::class);
    $token = $tokens->generar($a);

    expect($tokens->esValido($a, $token))->toBeTrue()
        ->and($tokens->esValido($b, $token))->toBeFalse()
        ->and($tokens->esValido($a, substr($token, 0, -1).($token[-1] === 'a' ? 'b' : 'a')))->toBeFalse()
        ->and($tokens->esValido($a, ''))->toBeFalse()
        ->and(strlen($token))->toBeGreaterThanOrEqual(32);
});

it('un token de QR viejo deja de servir pasada la ventana y la gracia', function () {
    $e = escenario(1);
    $sesion = SesionAsistencia::create(['actividad_id' => actividadPara($e->unidad)->id, 'abierta_por' => $e->dirigente->id, 'secreto' => bin2hex(random_bytes(32)), 'abierta_at' => now()]);
    $tokens = app(TokenRotativo::class);
    $viejo = $tokens->generar($sesion);

    $this->travel(2)->minutes();

    expect($tokens->esValido($sesion, $viejo))->toBeFalse();
});

it('el token de cobro es de 256 bits, no se repite y no aparece al serializar el cobro', function () {
    $tienda = usuarioTienda();
    $a = app(CrearCobro::class)($tienda, 5);
    $b = app(CrearCobro::class)($tienda, 5);

    expect(strlen($a->token))->toBe(64)->and($a->token)->not->toBe($b->token)->and(ctype_xdigit($a->token))->toBeTrue()
        ->and(json_encode($a))->not->toContain($a->token);
});

// ── XSS e inyección ─────────────────────────────────────────────────────────

it('los nombres y textos con HTML se muestran como texto, nunca como código', function () {
    $malo = '<img src=x onerror=alert(1)>';
    $e = escenario(2);
    $e->miembros[0]->update(['name' => $malo]);
    $mision = misionPara($e->unidad, ['titulo' => $malo, 'descripcion' => '<script>alert(2)</script>']);
    Entrega::factory()->create(['mision_id' => $mision->id, 'user_id' => $e->miembros[0]->id, 'texto' => '<script>alert(3)</script>']);

    $paginas = [
        [$e->dirigente, route('dirigente.inicio')],
        [$e->dirigente, route('dirigente.revisar', Entrega::first())],
        [$e->dirigente, route('dirigente.reservas')],
        [$e->miembros[1], route('app.misiones')],
        [$e->miembros[1], route('app.mision', $mision)],
    ];

    foreach ($paginas as [$user, $url]) {
        $html = $this->actingAs($user)->get($url)->getContent();
        expect($html)->not->toContain('<img src=x')->not->toContain('<script>alert');
    }
});

it('las búsquedas no se rompen ni filtran con comillas, comodines o intentos de inyección', function () {
    $e = escenario(2);
    $e->miembros[0]->update(['name' => "O'Brien 100%"]);
    $tienda = usuarioTienda();

    foreach (["'; DROP TABLE users; --", '%', '_', '" OR 1=1 --', '\\', "O'Brien"] as $q) {
        Livewire::actingAs($e->dirigente)->test(Penalizaciones::class)->set('buscar', $q)->assertOk();
        Livewire::actingAs($tienda)->test(Devolver::class)->set('buscar', $q)->assertOk();
    }

    expect(Schema::hasTable('users'))->toBeTrue();

    // «%» y «_» no son comodines: no devuelven a todo el mundo.
    Livewire::actingAs($e->dirigente)->test(Penalizaciones::class)->set('buscar', '%%')->assertViewHas('resultados', []);
});

// ── Producción ──────────────────────────────────────────────────────────────

it('la vista previa del diseño no se publica en producción', function () {
    $bootstrap = file_get_contents(base_path('routes/web.php'));

    expect($bootstrap)->toContain('! app()->isProduction()');
});

it('el comando de verificación detecta cuentas con el PIN de prueba', function () {
    User::factory()->create(['ci' => '4000001']);

    $this->artisan('jota:verificar-produccion')->expectsOutputToContain('PIN de prueba')->assertFailed();
});

it('el seeder de prueba se niega a correr en producción', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new DatabaseSeeder)->run())->toThrow(RuntimeException::class);
});

it('muchos PIN incorrectos dejan un aviso en el registro, sin bloquear a nadie', function () {
    $user = User::factory()->create();
    $dirigente = User::factory()->rol(Rol::Dirigente)->create();
    $login = app(LoginCiPin::class);

    Log::spy();

    foreach (range(1, 40) as $_) {
        try {
            $login($user->ci, '0000');
        } catch (PinIncorrecto) {
        }
    }
    Log::shouldHaveReceived('warning')->once();

    // Una cuenta con más poder avisa antes (a los 10) y también sigue pudiendo entrar con su PIN.
    foreach (range(1, 10) as $_) {
        try {
            $login($dirigente->ci, '0000');
        } catch (PinIncorrecto) {
        }
    }
    Log::shouldHaveReceived('warning')->twice();

    expect($login($user->ci, '1234')->is($user))->toBeTrue();
});
