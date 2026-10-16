<?php

use App\Domain\Actividades\QrAsistencia;
use App\Livewire\Dirigente\Asistencia as AsistenciaDirigente;
use App\Livewire\Dirigente\Cumplimientos;
use App\Livewire\Dirigente\Sesion;
use App\Models\Asistencia;
use App\Models\CumplimientoEquipo;
use App\Models\Patrulla;
use App\Models\SesionAsistencia;
use App\Models\Unidad;
use Livewire\Livewire;

beforeEach(function () {
    $this->e = escenario(3);
    $this->yo = $this->e->miembros[0];
    $this->dirigente = $this->e->dirigente;
    $this->actividad = actividadPara($this->e->unidad, ['titulo' => 'Nudos', 'puntos' => 20]);
});

// ── Dirigente: asistencia ───────────────────────────────────────────────────

it('el dirigente ve las actividades de su unidad y abre una sesión', function () {
    actividadPara(Unidad::factory()->create(), ['titulo' => 'Ajena']);

    $c = Livewire::actingAs($this->dirigente)->test(AsistenciaDirigente::class)->assertSee('Nudos')->assertDontSee('Ajena');
    $c->call('abrir', $this->actividad->id);

    $sesion = SesionAsistencia::sole();
    expect($sesion->abierta_por)->toBe($this->dirigente->id)->and($sesion->estaAbierta())->toBeTrue();
    $c->assertRedirect(route('dirigente.sesion', $sesion));
});

it('abrir una sesión ya abierta lleva a la que existe, sin duplicar', function () {
    $c = Livewire::actingAs($this->dirigente)->test(AsistenciaDirigente::class);
    $c->call('abrir', $this->actividad->id);
    $c->call('abrir', $this->actividad->id);

    expect(SesionAsistencia::count())->toBe(1);
});

it('una actividad por equipo no abre sesión y no se abre una de otra unidad', function () {
    $equipo = actividadPara($this->e->unidad, [], porEquipo: true);
    Livewire::actingAs($this->dirigente)->test(AsistenciaDirigente::class)->call('abrir', $equipo->id)->assertSet('error', fn ($e) => $e !== '');

    $ajena = actividadPara(Unidad::factory()->create());
    Livewire::actingAs($this->dirigente)->test(AsistenciaDirigente::class)->call('abrir', $ajena->id)->assertNotFound();
    expect(SesionAsistencia::count())->toBe(0);
});

it('la sesión muestra los asistentes, enlaza al QR y se cierra con confirmación', function () {
    $sesion = SesionAsistencia::create(['actividad_id' => $this->actividad->id, 'abierta_por' => $this->dirigente->id, 'secreto' => str_repeat('a', 64), 'abierta_at' => now()]);
    Asistencia::create(['actividad_id' => $this->actividad->id, 'user_id' => $this->yo->id, 'sesion_id' => $sesion->id]);

    $c = Livewire::actingAs($this->dirigente)->test(Sesion::class, ['sesion' => $sesion])
        ->assertSee(__('jota.dirigente.mostrar_qr'))->assertSee('<svg', false)->assertSee('/asistencia/'.$sesion->id.'?', false);

    $c->call('pedirCierre')->assertSee(__('jota.dirigente.cerrar_pregunta'));
    expect($sesion->fresh()->estaAbierta())->toBeTrue();

    $c->call('cerrar')->assertSee(__('jota.dirigente.sesion_cerrada_titulo'));
    expect($sesion->fresh()->estaAbierta())->toBeFalse();
});

it('un dirigente de otra unidad y un participante no entran a la sesión', function () {
    $sesion = SesionAsistencia::create(['actividad_id' => $this->actividad->id, 'abierta_por' => $this->dirigente->id, 'secreto' => str_repeat('a', 64), 'abierta_at' => now()]);

    $this->actingAs(escenario(1)->dirigente)->get(route('dirigente.sesion', $sesion))->assertForbidden();
    $this->actingAs($this->yo)->get(route('dirigente.sesion', $sesion))->assertForbidden();
});

// ── Participante: escanear y resultado ──────────────────────────────────────

function sesionAbiertaDe($t): SesionAsistencia
{
    return SesionAsistencia::create(['actividad_id' => $t->actividad->id, 'abierta_por' => $t->dirigente->id, 'secreto' => bin2hex(random_bytes(32)), 'abierta_at' => now()]);
}

it('la pantalla de escanear carga el escáner y la ayuda sin cámara', function () {
    $this->actingAs($this->yo)->get(route('app.escanear'))->assertOk()
        ->assertSee('data-escaner', false)->assertSee(__('jota.escanear.sin_camara_titulo'));
});

it('la ayuda sin cámara trae los pasos de cada navegador y cada motivo, para que el JS muestre el que corresponde', function () {
    $html = $this->actingAs($this->yo)->get(route('app.escanear'))->getContent();

    foreach (['ios-safari', 'ios-chrome', 'android-chrome', 'android-samsung', 'android-firefox', 'escritorio', 'integrado'] as $navegador) {
        expect($html)->toContain('data-navegador="'.$navegador.'"');
    }
    foreach (['inseguro', 'denegado', 'sin-camara', 'ocupada', 'otro'] as $motivo) {
        expect($html)->toContain('data-motivo="'.$motivo.'"');
    }
    expect($html)->toContain('aA')->not->toContain('candadito');
});

it('escanear el QR vigente muestra la pantalla de asistencia con los saldos nuevos', function () {
    $sesion = sesionAbiertaDe($this);

    $this->actingAs($this->yo)->get(app(QrAsistencia::class)->url($sesion))
        ->assertOk()->assertSee(__('jota.escanear.asistencia_ok_titulo'))->assertSee('Nudos');

    expect($this->yo->fresh())->puntos->toBe(20)->jlis->toBe(20);
});

it('escanear de nuevo avisa que ya estaba registrada, sin sumar otra vez', function () {
    $sesion = sesionAbiertaDe($this);
    $url = app(QrAsistencia::class)->url($sesion);

    $this->actingAs($this->yo)->get($url);
    $this->get($url)->assertOk()->assertSee(__('jota.escanear.asistencia_ya_titulo'));

    expect($this->yo->fresh()->puntos)->toBe(20);
});

it('un QR vencido, una sesión cerrada y una actividad de otra unidad muestran su aviso', function () {
    $sesion = sesionAbiertaDe($this);

    $this->actingAs($this->yo)->get(route('asistencia.registrar', ['sesion' => $sesion, 'token' => str_repeat('0', 32)]))
        ->assertStatus(422)->assertSee(__('jota.escanear.asistencia_vencido_titulo'));

    $this->actingAs(escenario(1)->miembros[0])->get(app(QrAsistencia::class)->url($sesion))
        ->assertForbidden()->assertSee(__('jota.escanear.asistencia_unidad_titulo'));

    $sesion->update(['cerrada_at' => now()]);
    $this->actingAs($this->yo)->get(app(QrAsistencia::class)->url($sesion))
        ->assertStatus(422)->assertSee(__('jota.escanear.asistencia_cerrada_titulo'));

    expect($this->yo->fresh()->puntos)->toBe(0);
});

it('el escaneo con JSON sigue respondiendo JSON', function () {
    $sesion = sesionAbiertaDe($this);

    $this->actingAs($this->yo)->getJson(app(QrAsistencia::class)->url($sesion))->assertOk()->assertJson(['estado' => 'ok', 'puntos' => 20]);
});

// ── Dirigente: cumplidas ────────────────────────────────────────────────────

it('el dirigente ve las actividades por equipo y marca patrullas con confirmación', function () {
    $equipo = actividadPara($this->e->unidad, ['titulo' => 'Gran juego', 'puntos' => 60], porEquipo: true);
    $otra = Patrulla::factory()->create(['unidad_id' => $this->e->unidad->id, 'nombre' => 'Zorros']);

    $this->actingAs($this->dirigente)->get(route('dirigente.cumplimientos'))->assertOk()->assertSee('Gran juego')->assertDontSee('Nudos');

    $c = Livewire::actingAs($this->dirigente)->test(Cumplimientos::class, ['actividad' => $equipo])
        ->assertSee($this->e->patrulla->nombre)->assertSee('Zorros');

    $c->call('pedirCumplida', 'p'.($this->e->patrulla->id))->assertSee(__('jota.dirigente.cumplida_pregunta', ['nombre' => $this->e->patrulla->nombre]));
    expect($this->e->patrulla->fresh()->puntos)->toBe(0);

    $c->call('cumplida');
    expect($this->e->patrulla->fresh()->puntos)->toBe(60)
        ->and($this->yo->fresh()->jlis)->toBe(20); // ⌈60/3⌉

    $c->call('noCumplida', 'p'.($otra->id));
    expect(CumplimientoEquipo::where('patrulla_id', $otra->id)->sole()->cumplida)->toBeFalse();

    // no cumplida → cumplida es posible; una cumplida no se vuelve a acreditar
    $c->call('pedirCumplida', 'p'.($otra->id))->call('cumplida');
    expect($otra->fresh()->puntos)->toBe(60);
    $c->call('pedirCumplida', 'p'.($this->e->patrulla->id))->call('cumplida')->assertSet('mensaje', __('jota.dirigente.cumplida_ya'));
    expect($this->e->patrulla->fresh()->puntos)->toBe(60);
});

it('no se marcan patrullas de otra unidad ni actividades ajenas', function () {
    $equipo = actividadPara($this->e->unidad, [], porEquipo: true);
    $ajena = escenario(1);

    Livewire::actingAs($this->dirigente)->test(Cumplimientos::class, ['actividad' => $equipo])->call('noCumplida', 'p'.($ajena->patrulla->id))->assertNotFound();

    $this->actingAs($ajena->dirigente)->get(route('dirigente.cumplimientos.actividad', $equipo))->assertForbidden();
    $this->actingAs($this->dirigente)->get(route('dirigente.cumplimientos.actividad', $this->actividad))->assertNotFound(); // individual
});
