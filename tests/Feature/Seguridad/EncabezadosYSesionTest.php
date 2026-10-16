<?php

use App\Domain\Tienda\CrearCobro;
use App\Livewire\Acceso\Ingresar;
use App\Models\SesionAsistencia;
use App\Models\User;
use Livewire\Livewire;

it('toda respuesta web lleva los encabezados de seguridad', function (string $ruta) {
    $r = $this->get($ruta);

    expect($r->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($r->headers->get('X-Frame-Options'))->toBe('DENY')
        ->and($r->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($r->headers->get('X-Robots-Tag'))->toContain('noindex')
        ->and($r->headers->get('Permissions-Policy'))->toContain('camera=(self)')->toContain('microphone=()')
        ->and($r->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'self'")->toContain("frame-ancestors 'none'")->toContain("object-src 'none'")->toContain("form-action 'self'");
})->with(['/ingresar', '/activar', '/admin/login', '/no-existe']);

it('la política de contenido no deja cargar nada de otros sitios', function () {
    $csp = $this->get('/ingresar')->headers->get('Content-Security-Policy');

    // Ninguna directiva admite un origen externo ni comodines.
    expect($csp)->not->toMatch('~https?://~')->not->toContain(' *;')->not->toContain('script-src *');
});

it('con https suma HSTS; con http, no', function () {
    expect($this->get('https://plataforma.test/ingresar')->headers->get('Strict-Transport-Security'))->toContain('max-age=')
        ->and($this->get('http://plataforma.test/ingresar')->headers->get('Strict-Transport-Security'))->toBeNull();
});

it('las respuestas JSON y de errores también llevan los encabezados', function () {
    $user = User::factory()->create();

    expect($this->actingAs($user)->getJson(route('ranking'))->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($this->actingAs($user)->get(route('app.mision', 99999))->headers->get('X-Frame-Options'))->toBe('DENY');
});

it('la cookie de sesión no se lee con JavaScript y no viaja en pedidos cruzados', function () {
    $user = User::factory()->create(['ci' => '4123456']);

    Livewire::test(Ingresar::class)->set('ci', '4123456')->set('pin', '1234')->call('ingresar');
    $cookie = collect($this->get('/ingresar')->headers->getCookies())->first(fn ($c) => str_contains($c->getName(), 'session'));

    expect($cookie)->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and(strtolower((string) $cookie->getSameSite()))->toBeIn(['lax', 'strict']);
});

it('al ingresar se renueva el identificador de sesión y al salir se invalida', function () {
    $user = User::factory()->create();

    $antes = session()->getId();
    Livewire::test(Ingresar::class)->set('ci', $user->ci)->set('pin', '1234')->call('ingresar');
    expect(session()->getId())->not->toBe($antes);

    $conSesion = session()->getId();
    $this->actingAs($user)->post(route('salir'));
    expect(session()->getId())->not->toBe($conSesion);
    $this->assertGuest();
});

it('la sesión larga del evento dura tres días y la cookie de producción es solo https', function () {
    expect(config('session.lifetime'))->toBe(4320);

    // En producción SESSION_SECURE_COOKIE=true (ver .env.production.example y jota:verificar-produccion).
    expect(file_get_contents(base_path('.env.production.example')))->toContain('SESSION_SECURE_COOKIE=true')->toContain('APP_DEBUG=false');
});

it('el usuario y la sesión de asistencia nunca exponen datos secretos al convertirse en JSON', function () {
    $user = User::factory()->create();
    $e = escenario(1);
    $sesion = SesionAsistencia::create(['actividad_id' => actividadPara($e->unidad)->id, 'abierta_por' => $e->dirigente->id, 'secreto' => 'x', 'abierta_at' => now()]);
    $cobro = app(CrearCobro::class)(usuarioTienda(), 10);

    expect($user->toArray())->not->toHaveKey('pin_hash')->not->toHaveKey('remember_token')
        ->and($sesion->toArray())->not->toHaveKey('secreto')
        ->and($cobro->toArray())->not->toHaveKey('token');
});

it('los saldos y el PIN no se pueden asignar en masa', function () {
    $f = (new User)->getFillable();

    expect($f)->not->toContain('puntos')->not->toContain('jlis')->not->toContain('pin_hash')->not->toContain('activado_at')->not->toContain('remember_token');
});

it('«recordarme» dura lo que el evento (3 días) y no 400 días', function () {
    $guardia = auth()->guard('web');
    $duracion = (new ReflectionMethod($guardia, 'getRememberDuration'))->invoke($guardia);

    expect(config('auth.guards.web.remember'))->toBe(4320)->and($duracion)->toBe(4320);
});
