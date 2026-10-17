<?php

use App\Domain\Contactos\AprobarContacto;
use App\Domain\Contactos\Puntaje;
use App\Domain\Contactos\RechazarContacto;
use App\Domain\Contactos\RegistrarContactoJota;
use App\Domain\Contactos\RegistrarContactoJoti;
use App\Enums\EstadoEntrega;
use App\Enums\Rol;
use App\Livewire\Beneficiario\RegistrarContacto;
use App\Livewire\Dirigente\RevisarContacto;
use App\Models\Contacto;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('private');
    $this->e = escenario(3);
    $this->yo = $this->e->miembros[0];
    $this->dir = $this->e->dirigente;
    $this->puntajes = fn () => app(Puntaje::class)->guardar(['jota_local' => 10, 'jota_exterior' => 30, 'joti_local' => 5, 'joti_exterior' => 15], adminDePrueba());
    $this->nuevoJoti = fn ($user = null, array $d = []) => app(RegistrarContactoJoti::class)($user ?? $this->yo, $d + ['grupo_scout' => 'Grupo Lima 1', 'ciudad' => 'Lima', 'unidad_scout' => 'Tropa', 'pais' => 'Perú'], fotoFalsa('chat.png', 800, 600));
    $this->nuevoJota = fn ($user = null, array $d = []) => app(RegistrarContactoJota::class)($user ?? $this->yo, $d + [
        'contactado_at' => now()->subHour()->format('Y-m-d H:i'), 'estacion' => 'ZP5ZDM', 'rst_tx' => '5-9', 'rst_rx' => '5-9', 'frecuencia' => '7.125', 'emision' => 'LSB',
    ]);
});

// ── Inicio y lista del participante ─────────────────────────────────────────

it('el Inicio muestra las tarjetas «Registro JOTA» y «Registro JOTI» con sus cuentas', function () {
    ($this->puntajes)();
    app(AprobarContacto::class)($this->dir, ($this->nuevoJoti)());
    ($this->nuevoJoti)();

    $this->actingAs($this->yo)->get(route('app.inicio'))->assertOk()
        ->assertSee('Registro JOTA')->assertSee('Registro JOTI')
        ->assertSee(route('app.contactos', 'jota'), false)->assertSee(route('app.contactos', 'joti'), false)
        ->assertSee('1 aprobado')->assertSee('1 por revisar');
});

it('la lista JOTI muestra solo mis contactos con su estado, y la de JOTA los de mi patrulla', function () {
    ($this->puntajes)();
    $mio = ($this->nuevoJoti)(null, ['ciudad' => 'Cusco']);
    ($this->nuevoJoti)($this->e->miembros[1], ['ciudad' => 'Quito']);
    $rechazado = ($this->nuevoJoti)(null, ['ciudad' => 'Bogotá']);
    app(RechazarContacto::class)($this->dir, $rechazado, 'La captura no se ve');
    ($this->nuevoJota)($this->e->miembros[1], ['estacion' => 'LU1ABC']);
    app(AprobarContacto::class)($this->dir, $mio);

    $this->actingAs($this->yo)->get(route('app.contactos', 'joti'))->assertOk()
        ->assertSee('Cusco, Perú')->assertSee('Aprobado')->assertSee('15 puntos')
        ->assertSee('Bogotá, Perú')->assertSee('Rechazado')->assertSee('La captura no se ve')->assertSee('no se puede volver a enviar')
        ->assertDontSee('Quito');

    $this->actingAs($this->yo)->get(route('app.contactos', 'jota'))->assertOk()
        ->assertSee('LU1ABC')->assertSee('Lo cargó '.$this->e->miembros[1]->name)->assertSee('Exterior')->assertDontSee('Cusco');
});

it('un tipo de contacto que no existe da 404 y solo los participantes entran', function () {
    $this->actingAs($this->yo)->get('/app/contactos/otro')->assertNotFound();
    $this->actingAs($this->dir)->get(route('app.contactos', 'joti'))->assertForbidden();
    auth()->logout();
    $this->get(route('app.contactos', 'joti'))->assertRedirect(route('ingresar'));
});

// ── Formularios ─────────────────────────────────────────────────────────────

it('carga un contacto JOTI desde el formulario, con país de Paraguay o de otro lugar', function () {
    $c = Livewire::actingAs($this->yo)->test(RegistrarContacto::class, ['tipo' => 'joti'])
        ->set('grupo_scout', 'Grupo Ñandejara')->set('ciudad', 'Encarnación')->set('unidad_scout', 'Caminantes')
        ->set('captura', fotoFalsa('chat.jpg', 700, 500))->call('enviar')->assertHasNoErrors()->assertSet('enviada', true);

    expect(Contacto::sole())->pais->toBe('Paraguay')->es_local->toBeTrue()->ciudad->toBe('Encarnación');

    Livewire::actingAs($this->yo)->test(RegistrarContacto::class, ['tipo' => 'joti'])
        ->set('grupo_scout', 'Lima 3')->set('ciudad', 'Lima')->set('unidad_scout', 'Tropa')->set('procedencia', 'otro')->set('pais_otro', 'Perú')
        ->set('captura', fotoFalsa())->call('enviar')->assertSet('enviada', true);

    expect(Contacto::where('pais', 'Perú')->sole()->es_local)->toBeFalse();
});

it('el formulario JOTI avisa de lo que falta y rechaza lo que no es una imagen', function () {
    Livewire::actingAs($this->yo)->test(RegistrarContacto::class, ['tipo' => 'joti'])
        ->set('ciudad', 'Lima')->set('unidad_scout', 'Tropa')->set('captura', fotoFalsa())->call('enviar')
        ->assertSet('enviada', false)->assertSet('error', 'Falta el grupo scout.');

    Livewire::actingAs($this->yo)->test(RegistrarContacto::class, ['tipo' => 'joti'])
        ->set('grupo_scout', 'a')->set('ciudad', 'b')->set('unidad_scout', 'c')->call('enviar')
        ->assertSet('error', 'Subí la captura de pantalla de la conversación.');

    Livewire::actingAs($this->yo)->test(RegistrarContacto::class, ['tipo' => 'joti'])
        ->set('captura', UploadedFile::fake()->createWithContent('x.png', 'no soy una imagen'))->assertHasErrors('captura')->assertSet('captura', null);

    expect(Contacto::count())->toBe(0);
});

it('carga un contacto JOTA desde el formulario y propone la fecha y la hora del servidor', function () {
    Livewire::actingAs($this->yo)->test(RegistrarContacto::class, ['tipo' => 'jota'])
        ->assertSet('fecha', now()->format('Y-m-d'))->assertSet('hora', now()->format('H:i'))
        ->set('hora', now()->subMinutes(20)->format('H:i'))
        ->set('estacion', 'zp6asp')->set('rst_tx', '5-4')->set('rst_rx', '5-9')->set('frecuencia', '14,025')->set('emision', 'USB')
        ->set('obs', 'Leo')->set('qsl_r', true)->call('enviar')->assertSet('enviada', true);

    expect(Contacto::sole())->estacion->toBe('ZP6ASP')->frecuencia->toBe('14.025')->emision->toBe('USB')->qsl_r->toBeTrue()->patrulla_id->toBe($this->e->patrulla->id);
});

it('el formulario JOTA explica los errores de los datos', function () {
    Livewire::actingAs($this->yo)->test(RegistrarContacto::class, ['tipo' => 'jota'])
        ->set('estacion', '123')->set('rst_tx', '5-9')->set('rst_rx', '5-9')->set('frecuencia', '7.1')->set('emision', 'LSB')->call('enviar')
        ->assertSet('enviada', false)->assertSet('error', 'El indicativo de la estación no parece válido (por ejemplo, ZP5ZDM).');

    Livewire::actingAs($this->yo)->test(RegistrarContacto::class, ['tipo' => 'jota'])
        ->set('estacion', 'ZP5ZDM')->set('rst_tx', '5-9')->set('rst_rx', '5-9')->set('frecuencia', '7.1')->call('enviar')
        ->assertSet('error', 'Elegí el modo de emisión.');
});

it('las pantallas de cargar contactos son solo para participantes', function () {
    $this->actingAs($this->yo)->get(route('app.contactos.nuevo', 'joti'))->assertOk()->assertSee('Nuevo contacto JOTI')->assertSee('Captura de la conversación');
    $this->actingAs($this->yo)->get(route('app.contactos.nuevo', 'jota'))->assertOk()->assertSee('Nuevo contacto JOTA')->assertSee('Modo de emisión')->assertSee('QSL R');
    $this->actingAs($this->dir)->get(route('app.contactos.nuevo', 'joti'))->assertForbidden();
});

// ── Dirigente ───────────────────────────────────────────────────────────────

it('el dirigente ve los contactos pendientes de su unidad (JOTA y JOTI) y no los de otra', function () {
    ($this->nuevoJoti)(null, ['ciudad' => 'Cusco']);
    ($this->nuevoJota)(null, ['estacion' => 'LU1ABC']);
    $otra = escenario(1);
    app(RegistrarContactoJoti::class)($otra->miembros[0], ['grupo_scout' => 'a', 'ciudad' => 'Ajena', 'unidad_scout' => 'c', 'pais' => 'd'], fotoFalsa());

    $this->actingAs($this->dir)->get(route('dirigente.contactos'))->assertOk()
        ->assertSee('2 contactos por revisar')->assertSee('Cusco, Perú')->assertSee('LU1ABC')->assertDontSee('Ajena');
    $this->actingAs($this->dir)->get(route('dirigente.inicio'))->assertOk()->assertSee('Contactos por revisar (2)');
    $this->actingAs($this->yo)->get(route('dirigente.contactos'))->assertForbidden();
});

it('la revisión muestra los datos y la captura, y aprobar acredita el puntaje definido', function () {
    ($this->puntajes)();
    $c = ($this->nuevoJoti)();

    $this->actingAs($this->dir)->get(route('dirigente.contacto', $c))->assertOk()
        ->assertSee('Lima, Perú')->assertSee('Grupo Lima 1')->assertSee('Exterior')->assertSee(route('contactos.captura', $c), false)
        ->assertSee('15 puntos');

    Livewire::actingAs($this->dir)->test(RevisarContacto::class, ['contacto' => $c])->call('aprobar')->assertSet('paso', 'aprobada');

    expect($c->fresh()->estado)->toBe(EstadoEntrega::Aprobada)->and($this->yo->fresh()->puntos)->toBe(15)->and($this->yo->fresh()->jlis)->toBe(15);
});

it('la revisión de un JOTA muestra el libro de guardia y reparte a la patrulla', function () {
    ($this->puntajes)();
    $c = ($this->nuevoJota)(null, ['obs' => 'Christian', 'qsl_e' => true]);

    $this->actingAs($this->dir)->get(route('dirigente.contacto', $c))->assertOk()
        ->assertSee('ZP5ZDM')->assertSee('7.125 MHz')->assertSee('LSB')->assertSee('Christian')->assertSee('Local')
        ->assertSee('10 puntos para la patrulla');

    Livewire::actingAs($this->dir)->test(RevisarContacto::class, ['contacto' => $c])->call('aprobar')->assertSet('paso', 'aprobada');

    expect($this->e->patrulla->fresh()->puntos)->toBe(10);
});

it('sin puntaje definido la revisión avisa y no aprueba', function () {
    $c = ($this->nuevoJoti)();

    $this->actingAs($this->dir)->get(route('dirigente.contacto', $c))->assertOk()->assertSee('todavía no definió el puntaje');
    Livewire::actingAs($this->dir)->test(RevisarContacto::class, ['contacto' => $c])->call('aprobar')->assertSet('sinPuntaje', true)->assertSet('paso', 'ver');

    expect($c->fresh()->estado)->toBe(EstadoEntrega::Pendiente);
});

it('rechazar pide el motivo y el contacto queda rechazado para siempre', function () {
    $c = ($this->nuevoJoti)();

    Livewire::actingAs($this->dir)->test(RevisarContacto::class, ['contacto' => $c])
        ->call('pedirRechazo')->assertSet('paso', 'rechazar')
        ->call('rechazar')->assertHasErrors('motivo')
        ->set('motivo', 'No se ve la conversación')->call('rechazar')->assertSet('paso', 'rechazada');

    expect($c->fresh())->estado->toBe(EstadoEntrega::Rechazada)->motivo_rechazo->toBe('No se ve la conversación');

    // Ya revisado: al volver a abrirlo, no se puede aprobar.
    Livewire::actingAs($this->dir)->test(RevisarContacto::class, ['contacto' => $c->fresh()])->assertSet('paso', 'ya');
});

it('el dirigente de otra unidad recibe 403 en la revisión y en la captura; el admin puede ver todo', function () {
    $c = ($this->nuevoJoti)();
    $ajeno = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => Unidad::factory()->create()->id]);

    $this->actingAs($ajeno)->get(route('dirigente.contacto', $c))->assertForbidden();
    $this->actingAs($ajeno)->get(route('contactos.captura', $c))->assertForbidden();
    $this->actingAs(adminDePrueba())->get(route('dirigente.contacto', $c))->assertOk();
    $this->actingAs(adminDePrueba())->get(route('contactos.captura', $c))->assertOk();
});

// ── Privacidad de la captura ────────────────────────────────────────────────

it('la captura la ven solo quien la cargó y quienes revisan, siempre como JPEG sin EXIF', function () {
    $c = ($this->nuevoJoti)();
    $otroCompa = $this->e->miembros[1];

    $r = $this->actingAs($this->yo)->get(route('contactos.captura', $c))->assertOk();
    expect($r->headers->get('Content-Type'))->toBe('image/jpeg')->and($r->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    $this->actingAs($this->dir)->get(route('contactos.captura', $c))->assertOk();
    $this->actingAs($otroCompa)->get(route('contactos.captura', $c))->assertForbidden(); // ni siquiera su patrulla: JOTI es personal
    auth()->logout();
    $this->get(route('contactos.captura', $c))->assertRedirect(route('ingresar'));
});

it('una captura sin procesar nunca se sirve', function () {
    $c = ($this->nuevoJoti)();
    $c->forceFill(['captura_procesada_at' => null, 'captura_ruta' => 'tmp/original-con-exif.jpg'])->save();

    $this->actingAs($this->yo)->get(route('contactos.captura', $c))->assertNotFound();
});

it('la purga borra también las capturas de los contactos', function () {
    $c = ($this->nuevoJoti)()->fresh();
    Storage::disk('private')->assertExists($c->captura_ruta);

    $this->artisan('jota:purgar-entregas --force')->assertSuccessful();

    expect(Storage::disk('private')->allFiles())->toBe([])->and($c->fresh()->captura_ruta)->toBeNull();
});
