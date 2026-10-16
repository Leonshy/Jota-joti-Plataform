<?php

use App\Domain\Actividades\AbrirSesionAsistencia;
use App\Domain\Actividades\AsistenciaYaRegistrada;
use App\Domain\Actividades\CerrarSesionAsistencia;
use App\Domain\Actividades\ModalidadIncorrecta;
use App\Domain\Actividades\QrAsistencia;
use App\Domain\Actividades\RegistrarAsistencia;
use App\Domain\Actividades\SesionCerrada;
use App\Domain\Actividades\SesionYaAbierta;
use App\Domain\Actividades\TokenInvalido;
use App\Domain\Actividades\TokenRotativo;
use App\Domain\Ledger\Reconciliador;
use App\Enums\Rol;
use App\Models\Asistencia;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\SesionAsistencia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->e = escenario(3);
    $this->yo = $this->e->miembros[0];
    $this->actividad = actividadPara($this->e->unidad, ['puntos' => 20, 'titulo' => 'Taller de nudos']);
    $this->tokens = app(TokenRotativo::class);
});

function sesionAbierta($test): SesionAsistencia
{
    return app(AbrirSesionAsistencia::class)($test->e->dirigente, $test->actividad);
}

// ── Token rotativo ──────────────────────────────────────────────────────────

/** Un instante alineado al inicio de una ventana de 60 s, más $segundos. */
function enVentana(int $ventanas, int $segundos = 0): Carbon
{
    return Carbon::createFromTimestamp((intdiv(time(), 60) + $ventanas) * 60 + $segundos);
}

it('el token es el mismo dentro de la ventana y cambia en la siguiente', function () {
    $sesion = sesionAbierta($this);

    $a = $this->tokens->generar($sesion, enVentana(0, 1));
    $b = $this->tokens->generar($sesion, enVentana(0, 59));
    $c = $this->tokens->generar($sesion, enVentana(1, 0));

    expect($a)->toBe($b)->and($c)->not->toBe($a)->and($a)->toHaveLength(32);
});

it('acepta el token de la ventana actual', function () {
    $sesion = sesionAbierta($this);
    $ahora = enVentana(0, 30);

    expect($this->tokens->esValido($sesion, $this->tokens->generar($sesion, $ahora), $ahora))->toBeTrue();
});

it('acepta el token de la ventana anterior solo durante los 10 s de gracia', function () {
    $sesion = sesionAbierta($this);
    $viejo = $this->tokens->generar($sesion, enVentana(0, 30));

    expect($this->tokens->esValido($sesion, $viejo, enVentana(1, 0)))->toBeTrue()
        ->and($this->tokens->esValido($sesion, $viejo, enVentana(1, 9)))->toBeTrue()
        ->and($this->tokens->esValido($sesion, $viejo, enVentana(1, 10)))->toBeFalse()
        ->and($this->tokens->esValido($sesion, $viejo, enVentana(1, 45)))->toBeFalse();
});

it('rechaza tokens de ventanas futuras, de dos ventanas atrás y basura', function () {
    $sesion = sesionAbierta($this);
    $ahora = enVentana(5, 3);

    expect($this->tokens->esValido($sesion, $this->tokens->generar($sesion, enVentana(6)), $ahora))->toBeFalse()
        ->and($this->tokens->esValido($sesion, $this->tokens->generar($sesion, enVentana(3)), $ahora))->toBeFalse()
        ->and($this->tokens->esValido($sesion, 'basura', $ahora))->toBeFalse()
        ->and($this->tokens->esValido($sesion, '', $ahora))->toBeFalse();
});

it('un token de una sesión no sirve en otra', function () {
    $sesion = sesionAbierta($this);
    app(CerrarSesionAsistencia::class)($this->e->dirigente, $sesion);
    $otra = sesionAbierta($this);
    $ahora = enVentana(0, 30);

    expect($this->tokens->esValido($otra, $this->tokens->generar($sesion, $ahora), $ahora))->toBeFalse();
});

it('informa cuántos segundos faltan para que rote', function () {
    expect($this->tokens->segundosRestantes(enVentana(0, 0)))->toBe(60)
        ->and($this->tokens->segundosRestantes(enVentana(0, 45)))->toBe(15);
});

// ── Abrir y cerrar sesión ───────────────────────────────────────────────────

it('el dirigente de la unidad abre una sesión con un secreto aleatorio', function () {
    $a = sesionAbierta($this);
    app(CerrarSesionAsistencia::class)($this->e->dirigente, $a);
    $b = sesionAbierta($this);

    expect($a->abierta_por)->toBe($this->e->dirigente->id)
        ->and($a->secreto)->toHaveLength(64)->and($b->secreto)->not->toBe($a->secreto)
        ->and($a->toArray())->not->toHaveKey('secreto'); // no se serializa
});

it('una sola sesión abierta por actividad, y se puede abrir otra al cerrar', function () {
    $primera = sesionAbierta($this);

    try {
        sesionAbierta($this);
        $this->fail('Debió rechazar');
    } catch (SesionYaAbierta $e) {
        expect($e->sesion->id)->toBe($primera->id);
    }

    app(CerrarSesionAsistencia::class)($this->e->dirigente, $primera);

    expect(sesionAbierta($this)->id)->not->toBe($primera->id);
});

it('el dirigente de otra unidad y el beneficiario no abren sesiones', function () {
    expect(fn () => app(AbrirSesionAsistencia::class)(escenario()->dirigente, $this->actividad))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(AbrirSesionAsistencia::class)($this->yo, $this->actividad))
        ->toThrow(AuthorizationException::class);
});

it('el admin abre sesiones en cualquier actividad', function () {
    $admin = User::factory()->rol(Rol::Admin)->create();

    expect(app(AbrirSesionAsistencia::class)($admin, $this->actividad)->abierta_por)->toBe($admin->id);
});

it('las actividades por equipo no registran asistencia', function () {
    app(AbrirSesionAsistencia::class)($this->e->dirigente, actividadPara($this->e->unidad, [], true));
})->throws(ModalidadIncorrecta::class);

it('el turno de la sesión debe ser de la actividad', function () {
    $otroTurno = turnoDe(actividadPara($this->e->unidad));

    app(AbrirSesionAsistencia::class)($this->e->dirigente, $this->actividad, $otroTurno);
})->throws(ModalidadIncorrecta::class);

it('cerrar es idempotente y solo lo hace quien gestiona la unidad', function () {
    $sesion = sesionAbierta($this);

    expect(fn () => app(CerrarSesionAsistencia::class)(escenario()->dirigente, $sesion))->toThrow(AuthorizationException::class);

    app(CerrarSesionAsistencia::class)($this->e->dirigente, $sesion);
    $cerradaAt = $sesion->fresh()->cerrada_at;
    $this->travel(5)->minutes();
    app(CerrarSesionAsistencia::class)($this->e->dirigente, $sesion);

    expect($sesion->fresh()->cerrada_at->equalTo($cerradaAt))->toBeTrue();
});

// ── Registrar asistencia ────────────────────────────────────────────────────

it('registra la asistencia y acredita puntos y LisCoins al instante, sin reserva ni aprobación', function () {
    $sesion = sesionAbierta($this);

    $asistencia = app(RegistrarAsistencia::class)($this->yo, $sesion, $this->tokens->generar($sesion));

    $yo = $this->yo->fresh();
    expect($yo->puntos)->toBe(20)->and($yo->jlis)->toBe(20)
        ->and($asistencia->actividad_id)->toBe($this->actividad->id)
        ->and(MovPuntoIndividual::first()->origen_id)->toBe($asistencia->id)
        ->and($this->e->patrulla->fresh()->puntos)->toBe(0); // actividad individual: nada a la patrulla
});

it('un segundo escaneo responde "ya registrado" y no suma de nuevo', function () {
    $sesion = sesionAbierta($this);
    $registrar = app(RegistrarAsistencia::class);
    $registrar($this->yo, $sesion, $this->tokens->generar($sesion));

    expect(fn () => $registrar($this->yo, $sesion, $this->tokens->generar($sesion)))->toThrow(AsistenciaYaRegistrada::class)
        ->and(Asistencia::count())->toBe(1)
        ->and($this->yo->fresh()->puntos)->toBe(20)
        ->and(MovJli::count())->toBe(1);
});

it('una sesión nueva de la misma actividad no vuelve a sumar a quien ya asistió', function () {
    $primera = sesionAbierta($this);
    app(RegistrarAsistencia::class)($this->yo, $primera, $this->tokens->generar($primera));
    app(CerrarSesionAsistencia::class)($this->e->dirigente, $primera);
    $segunda = sesionAbierta($this);

    expect(fn () => app(RegistrarAsistencia::class)($this->yo, $segunda, $this->tokens->generar($segunda)))
        ->toThrow(AsistenciaYaRegistrada::class);
});

it('cada persona suma por su cuenta y cada actividad suma aparte', function () {
    $sesion = sesionAbierta($this);
    $otraActividad = actividadPara($this->e->unidad, ['puntos' => 5]);
    $sesion2 = app(AbrirSesionAsistencia::class)($this->e->dirigente, $otraActividad);
    $registrar = app(RegistrarAsistencia::class);

    $registrar($this->e->miembros[0], $sesion, $this->tokens->generar($sesion));
    $registrar($this->e->miembros[1], $sesion, $this->tokens->generar($sesion));
    $registrar($this->e->miembros[0], $sesion2, $this->tokens->generar($sesion2));

    expect($this->e->miembros[0]->fresh()->puntos)->toBe(25)
        ->and($this->e->miembros[1]->fresh()->puntos)->toBe(20)
        ->and(Asistencia::count())->toBe(3);
});

it('rechaza un QR vencido y no registra nada', function () {
    $sesion = sesionAbierta($this);
    $viejo = $this->tokens->generar($sesion, now()->subMinutes(5));

    expect(fn () => app(RegistrarAsistencia::class)($this->yo, $sesion, $viejo))->toThrow(TokenInvalido::class)
        ->and(Asistencia::count())->toBe(0)
        ->and($this->yo->fresh()->puntos)->toBe(0);
});

it('rechaza el escaneo si la sesión ya se cerró', function () {
    $sesion = sesionAbierta($this);
    $token = $this->tokens->generar($sesion);
    app(CerrarSesionAsistencia::class)($this->e->dirigente, $sesion);

    app(RegistrarAsistencia::class)($this->yo, $sesion, $token);
})->throws(SesionCerrada::class);

it('solo registran los beneficiarios de las unidades destino', function () {
    $sesion = sesionAbierta($this);
    $token = $this->tokens->generar($sesion);
    $registrar = app(RegistrarAsistencia::class);

    expect(fn () => $registrar(escenario()->miembros[0], $sesion, $token))->toThrow(AuthorizationException::class)
        ->and(fn () => $registrar($this->e->dirigente, $sesion, $token))->toThrow(AuthorizationException::class)
        ->and(Asistencia::count())->toBe(0);
});

it('una actividad de 0 puntos registra la asistencia sin movimientos (D4)', function () {
    $actividad = actividadPara($this->e->unidad, ['puntos' => 0]);
    $sesion = app(AbrirSesionAsistencia::class)($this->e->dirigente, $actividad);

    app(RegistrarAsistencia::class)($this->yo, $sesion, $this->tokens->generar($sesion));

    expect(Asistencia::count())->toBe(1)->and(MovPuntoIndividual::count())->toBe(0)->and(MovJli::count())->toBe(0);
});

it('la reconciliación queda limpia después de asistencias', function () {
    $sesion = sesionAbierta($this);
    foreach ($this->e->miembros as $m) {
        app(RegistrarAsistencia::class)($m, $sesion, $this->tokens->generar($sesion));
    }

    expect(app(Reconciliador::class)->diferencias())->toBe([]);
});

// ── Pantalla pública y endpoint de registro ─────────────────────────────────

it('la pantalla pública solo abre con el enlace firmado', function () {
    $sesion = sesionAbierta($this);

    $this->get($sesion->urlPublica())->assertOk()->assertSee('Taller de nudos');
    $this->get(route('asistencia.publica', $sesion))->assertForbidden();
    $this->get($sesion->urlPublica().'x')->assertForbidden();
    $this->get(route('asistencia.qr', $sesion))->assertForbidden();
});

it('una firma de una sesión no sirve para otra', function () {
    $a = sesionAbierta($this);
    $b = (function () use ($a) {
        app(CerrarSesionAsistencia::class)($this->e->dirigente, $a);

        return sesionAbierta($this);
    })();

    $firmadaDeA = $a->urlPublica();
    $manipulada = str_replace("/asistencia/{$a->id}", "/asistencia/{$b->id}", $firmadaDeA);

    $this->get($manipulada)->assertForbidden();
});

it('el endpoint firmado entrega un SVG con el QR y los segundos que faltan', function () {
    $sesion = sesionAbierta($this);
    $qr = URL::signedRoute('asistencia.qr', ['sesion' => $sesion]);

    $respuesta = $this->getJson($qr)->assertOk();

    expect($respuesta->json('svg'))->toStartWith('<?xml')->toContain('<svg')
        ->and($respuesta->json('segundos'))->toBeInt()->toBeBetween(1, 60)
        ->and($respuesta->headers->get('Cache-Control'))->toContain('no-store');
});

it('el QR cambia en la ventana siguiente', function () {
    $sesion = sesionAbierta($this);
    $qr = app(QrAsistencia::class);

    $antes = $qr->url($sesion);
    $this->travel(61)->seconds();

    expect($qr->url($sesion))->not->toBe($antes);
});

it('al cerrar la sesión el enlace público y el QR dejan de servir (410)', function () {
    $sesion = sesionAbierta($this);
    $publica = $sesion->urlPublica();
    $qr = URL::signedRoute('asistencia.qr', ['sesion' => $sesion]);
    $this->get($publica)->assertOk();

    app(CerrarSesionAsistencia::class)($this->e->dirigente, $sesion);

    $this->get($publica)->assertStatus(410);
    $this->getJson($qr)->assertStatus(410);
});

it('al escanear el QR, quien está logueado registra su asistencia', function () {
    $sesion = sesionAbierta($this);
    $url = app(QrAsistencia::class)->url($sesion);

    $this->actingAs($this->yo)->getJson($url)
        ->assertOk()->assertJson(['estado' => 'ok', 'puntos' => 20]);

    $this->actingAs($this->yo)->getJson($url)
        ->assertOk()->assertJson(['estado' => 'ya_registrado']);

    expect(Asistencia::count())->toBe(1)->and($this->yo->fresh()->puntos)->toBe(20);
});

it('el escaneo sin sesión iniciada manda a ingresar', function () {
    $sesion = sesionAbierta($this);

    $this->get(app(QrAsistencia::class)->url($sesion))->assertRedirect(route('ingresar'));
});

it('el escaneo con un QR vencido o de otra unidad no registra', function () {
    $sesion = sesionAbierta($this);
    $vencido = route('asistencia.registrar', ['sesion' => $sesion, 'token' => 'f'.str_repeat('0', 31)]);

    $this->actingAs($this->yo)->getJson($vencido)->assertStatus(422)->assertJson(['estado' => 'vencido']);
    $this->actingAs(escenario()->miembros[0])->getJson(app(QrAsistencia::class)->url($sesion))->assertForbidden();

    expect(Asistencia::count())->toBe(0);
});
