<?php

use App\Domain\Contactos\AprobarContacto;
use App\Domain\Contactos\ColaContactos;
use App\Domain\Contactos\ContactoInvalido;
use App\Domain\Contactos\ContactosDelParticipante;
use App\Domain\Contactos\ContactoYaRevisado;
use App\Domain\Contactos\Procedencia;
use App\Domain\Contactos\Puntaje;
use App\Domain\Contactos\PuntajeSinConfigurar;
use App\Domain\Contactos\RechazarContacto;
use App\Domain\Contactos\RegistrarContactoJota;
use App\Domain\Contactos\RegistrarContactoJoti;
use App\Enums\EstadoEntrega;
use App\Enums\Rol;
use App\Enums\TipoContacto;
use App\Models\Contacto;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\MovPuntoPatrulla;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Storage::fake('private');
    $this->e = escenario(4);
    $this->yo = $this->e->miembros[0];
    $this->puntaje = fn (array $v = []) => app(Puntaje::class)->guardar($v + ['jota_local' => 10, 'jota_exterior' => 30, 'joti_local' => 5, 'joti_exterior' => 15], adminDePrueba());
    $this->joti = fn (array $d = [], $captura = 'ok') => app(RegistrarContactoJoti::class)(
        $this->yo,
        $d + ['grupo_scout' => 'Grupo Scout Lima 1', 'ciudad' => 'Lima', 'unidad_scout' => 'Tropa', 'pais' => 'Perú'],
        $captura === 'ok' ? fotoFalsa('chat.png', 900, 600) : $captura,
    );
    $this->jota = fn (array $d = [], $user = null) => app(RegistrarContactoJota::class)($user ?? $this->yo, $d + [
        'contactado_at' => now()->subHour()->format('Y-m-d H:i'), 'estacion' => 'ZP5ZDM', 'rst_tx' => '59', 'rst_rx' => '5-9',
        'frecuencia' => '7,125', 'emision' => 'LSB', 'obs' => 'Christian', 'qsl_r' => false, 'qsl_e' => true,
    ]);
});

// ── De dónde es el contacto ─────────────────────────────────────────────────

it('es local si el país es Paraguay o el indicativo empieza con ZP', function () {
    expect(Procedencia::esPaisLocal(' paraguay '))->toBeTrue()
        ->and(Procedencia::esPaisLocal('PARAGUAY'))->toBeTrue()
        ->and(Procedencia::esPaisLocal('Perú'))->toBeFalse()
        ->and(Procedencia::esIndicativoLocal('zp5zdm'))->toBeTrue()
        ->and(Procedencia::esIndicativoLocal('LU1ABC'))->toBeFalse();
});

// ── JOTI ────────────────────────────────────────────────────────────────────

it('registra un contacto JOTI pendiente, con la captura re-codificada y sin datos personales de más', function () {
    $c = ($this->joti)()->fresh();

    expect($c->tipo)->toBe(TipoContacto::Joti)->and($c->estado)->toBe(EstadoEntrega::Pendiente)
        ->and($c->user_id)->toBe($this->yo->id)->and($c->unidad_id)->toBe($this->e->unidad->id)
        ->and($c->patrulla_id)->toBeNull()->and($c->es_local)->toBeFalse()
        ->and($c->captura_procesada_at)->not->toBeNull()
        ->and($c->captura_ruta)->toStartWith("contactos/{$c->id}/")->toEndWith('.jpg');
    Storage::disk('private')->assertExists($c->captura_ruta);
    expect(Storage::disk('private')->allFiles())->toHaveCount(1); // el original de tmp/ ya no está
});

it('un contacto JOTI de Paraguay es local', function () {
    expect(($this->joti)(['pais' => 'Paraguay'])->es_local)->toBeTrue();
});

it('no hay tope de contactos JOTI por persona', function () {
    foreach (range(1, 12) as $_) {
        ($this->joti)();
    }

    expect(Contacto::where('user_id', $this->yo->id)->count())->toBe(12);
});

it('JOTI pide todos los datos y la captura, y la captura tiene que ser una imagen real', function () {
    foreach (['grupo_scout', 'ciudad', 'unidad_scout', 'pais'] as $campo) {
        expect(fn () => ($this->joti)([$campo => '  ']))->toThrow(ContactoInvalido::class);
    }
    expect(fn () => ($this->joti)([], null))->toThrow(ContactoInvalido::class, 'captura');

    $falsa = UploadedFile::fake()->createWithContent('chat.png', '<?php echo 1;');
    expect(fn () => ($this->joti)([], $falsa))->toThrow(ContactoInvalido::class);
    expect(fn () => ($this->joti)(['ciudad' => str_repeat('a', 121)]))->toThrow(ContactoInvalido::class);
    expect(Contacto::count())->toBe(0)->and(Storage::disk('private')->allFiles())->toBe([]);
});

it('solo los participantes registran contactos', function () {
    $joti = fn ($u) => app(RegistrarContactoJoti::class)($u, ['grupo_scout' => 'a', 'ciudad' => 'b', 'unidad_scout' => 'c', 'pais' => 'd'], fotoFalsa());

    expect(fn () => $joti($this->e->dirigente))->toThrow(AuthorizationException::class)
        ->and(fn () => $joti(adminDePrueba()))->toThrow(AuthorizationException::class);
});

// ── JOTA ────────────────────────────────────────────────────────────────────

it('registra un contacto JOTA para la patrulla, normaliza los datos y deduce que es local por el indicativo', function () {
    $c = ($this->jota)(['estacion' => ' zp5 zdm '])->fresh();

    expect($c->tipo)->toBe(TipoContacto::Jota)->and($c->estado)->toBe(EstadoEntrega::Pendiente)
        ->and($c->patrulla_id)->toBe($this->e->patrulla->id)->and($c->unidad_id)->toBe($this->e->unidad->id)
        ->and($c->estacion)->toBe('ZP5ZDM')->and($c->es_local)->toBeTrue()
        ->and($c->rst_tx)->toBe('5-9')->and($c->rst_rx)->toBe('5-9')->and($c->frecuencia)->toBe('7.125')
        ->and($c->emision)->toBe('LSB')->and($c->obs)->toBe('Christian')->and($c->qsl_r)->toBeFalse()->and($c->qsl_e)->toBeTrue()
        ->and($c->captura_ruta)->toBeNull();
});

it('un indicativo de otro país es del exterior, y la frecuencia entera no se rompe', function () {
    $c = ($this->jota)(['estacion' => 'LU1ABC', 'frecuencia' => '100', 'rst_tx' => '599'])->fresh();

    expect($c->es_local)->toBeFalse()->and($c->frecuencia)->toBe('100')->and($c->rst_tx)->toBe('5-9-9');
});

it('JOTA valida los datos del libro de guardia', function (array $mal) {
    expect(fn () => ($this->jota)($mal))->toThrow(ContactoInvalido::class);
})->with([
    'sin fecha' => [['contactado_at' => '']],
    'fecha futura' => [fn () => ['contactado_at' => now()->addDay()->format('Y-m-d H:i')]],
    'indicativo inválido' => [['estacion' => '12345']],
    'RST fuera de rango' => [['rst_tx' => '69']],
    'RST de recepción inválido' => [['rst_rx' => 'xx']],
    'frecuencia con letras' => [['frecuencia' => 'siete']],
    'frecuencia cero' => [['frecuencia' => '0']],
    'emisión desconocida' => [['emision' => 'MAGIA']],
    'observaciones largas' => [['obs' => str_repeat('a', 121)]],
]);

it('no deja cargar dos veces el mismo contacto (estación, fecha y hora) del equipo, pero un rechazado se puede volver a cargar', function () {
    $c = ($this->jota)();
    expect(fn () => ($this->jota)([], $this->e->miembros[1]))->toThrow(ContactoInvalido::class, 'ya está cargado');

    // Otra patrulla sí puede tener el mismo contacto.
    $otra = escenario(2);
    expect(($this->jota)([], $otra->miembros[0]))->toBeInstanceOf(Contacto::class);

    // Un rechazado no impide volver a cargarlo (es un registro nuevo).
    app(RechazarContacto::class)($this->e->dirigente, $c, 'No coincide la hora');
    expect(($this->jota)()->estado)->toBe(EstadoEntrega::Pendiente);
});

it('JOTA necesita equipo: sin patrulla y fuera de los Rovers no se puede registrar', function () {
    $sin = User::factory()->create(['unidad_id' => $this->e->unidad->id, 'patrulla_id' => null]);

    expect(fn () => ($this->jota)([], $sin))->toThrow(ContactoInvalido::class, 'patrulla');
});

// ── Puntaje ─────────────────────────────────────────────────────────────────

it('el puntaje de los contactos lo define el admin y valida que sean enteros de 0 a 100.000', function () {
    $p = app(Puntaje::class);
    expect($p->todos())->toBe(['jota_local' => null, 'jota_exterior' => null, 'joti_local' => null, 'joti_exterior' => null]);

    ($this->puntaje)();
    expect($p->para(TipoContacto::Jota, true))->toBe(10)->and($p->para(TipoContacto::Jota, false))->toBe(30)
        ->and($p->para(TipoContacto::Joti, true))->toBe(5)->and($p->para(TipoContacto::Joti, false))->toBe(15);

    foreach ([-1, 'x', '', null, 100001, '1.5'] as $mal) {
        expect(fn () => ($this->puntaje)(['joti_local' => $mal]))->toThrow(ContactoInvalido::class);
    }
    expect($p->para(TipoContacto::Joti, true))->toBe(5);
    expect(Activity::where('description', 'Cambió el puntaje de los contactos')->count())->toBe(1);
});

// ── Aprobar ─────────────────────────────────────────────────────────────────

it('aprobar un JOTI suma puntos y LisCoins a la persona, local o del exterior según el país', function () {
    ($this->puntaje)();
    $local = ($this->joti)(['pais' => 'Paraguay']);
    $afuera = ($this->joti)(['pais' => 'Perú']);

    app(AprobarContacto::class)($this->e->dirigente, $local);
    expect($this->yo->fresh()->puntos)->toBe(5)->and($this->yo->fresh()->jlis)->toBe(5);

    app(AprobarContacto::class)($this->e->dirigente, $afuera);
    expect($this->yo->fresh()->puntos)->toBe(20)->and($this->yo->fresh()->jlis)->toBe(20)
        ->and($afuera->fresh())->estado->toBe(EstadoEntrega::Aprobada)->puntos->toBe(15)->revisado_por->toBe($this->e->dirigente->id)
        ->and($this->e->patrulla->fresh()->puntos)->toBe(0); // JOTI no toca a la patrulla
    expect(MovPuntoIndividual::count())->toBe(2)->and(MovJli::count())->toBe(2);
});

it('aprobar un JOTA suma puntos a la patrulla y reparte ⌈P/n⌉ LisCoins a cada integrante', function () {
    ($this->puntaje)(['jota_local' => 10]);
    $c = ($this->jota)();

    app(AprobarContacto::class)($this->e->dirigente, $c);

    expect($this->e->patrulla->fresh()->puntos)->toBe(10)->and(MovPuntoPatrulla::count())->toBe(1)
        ->and($this->yo->fresh()->puntos)->toBe(0); // los puntos de JOTA son de la patrulla, no de la persona
    foreach ($this->e->miembros as $m) {
        expect($m->fresh()->jlis)->toBe(3); // ⌈10/4⌉
    }
});

it('un JOTA del exterior vale el otro puntaje', function () {
    ($this->puntaje)(['jota_exterior' => 40]);

    app(AprobarContacto::class)($this->e->dirigente, ($this->jota)(['estacion' => 'LU1ABC']));

    expect($this->e->patrulla->fresh()->puntos)->toBe(40);
});

it('en los Rovers un JOTA da a cada Rover los puntos y los LisCoins completos', function () {
    $rovers = Unidad::factory()->individual()->create();
    $r = User::factory()->count(3)->create(['unidad_id' => $rovers->id, 'patrulla_id' => null]);
    $dir = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => $rovers->id]);
    ($this->puntaje)(['jota_local' => 10]);

    $c = ($this->jota)([], $r[0]);
    expect($c->patrulla_id)->toBeNull()->and($c->unidad_id)->toBe($rovers->id);
    app(AprobarContacto::class)($dir, $c);

    foreach ($r as $rover) {
        expect($rover->fresh()->puntos)->toBe(10)->and($rover->fresh()->jlis)->toBe(10);
    }
});

it('sin puntaje configurado no se puede aprobar y el contacto sigue pendiente', function () {
    $c = ($this->joti)();

    expect(fn () => app(AprobarContacto::class)($this->e->dirigente, $c))->toThrow(PuntajeSinConfigurar::class);
    expect($c->fresh()->estado)->toBe(EstadoEntrega::Pendiente)->and($this->yo->fresh()->puntos)->toBe(0)->and(MovPuntoIndividual::count())->toBe(0);
});

it('un puntaje de 0 se puede aprobar y no acredita nada', function () {
    ($this->puntaje)(['joti_exterior' => 0]);
    $c = ($this->joti)();

    app(AprobarContacto::class)($this->e->dirigente, $c);

    expect($c->fresh()->estado)->toBe(EstadoEntrega::Aprobada)->and(MovPuntoIndividual::count())->toBe(0)->and(MovJli::count())->toBe(0);
});

it('se aprueba una sola vez: la segunda queda como «ya revisado» y no suma de nuevo', function () {
    ($this->puntaje)();
    $c = ($this->joti)();
    app(AprobarContacto::class)($this->e->dirigente, $c);

    expect(fn () => app(AprobarContacto::class)($this->e->dirigente, $c))->toThrow(ContactoYaRevisado::class);
    expect($this->yo->fresh()->puntos)->toBe(15)->and(MovPuntoIndividual::count())->toBe(1);
});

it('el admin también aprueba, y el dirigente de otra unidad recibe 403', function () {
    ($this->puntaje)();
    $c = ($this->joti)();
    $ajeno = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => Unidad::factory()->create()->id]);

    expect(fn () => app(AprobarContacto::class)($ajeno, $c))->toThrow(AuthorizationException::class)
        ->and(fn () => app(RechazarContacto::class)($ajeno, $c, 'no'))->toThrow(AuthorizationException::class)
        ->and(fn () => app(AprobarContacto::class)($this->yo, $c))->toThrow(AuthorizationException::class);

    app(AprobarContacto::class)(adminDePrueba(), $c);
    expect($c->fresh()->estado)->toBe(EstadoEntrega::Aprobada);
});

// ── Rechazar ────────────────────────────────────────────────────────────────

it('rechazar pide el motivo y es definitivo: no se puede aprobar ni volver a rechazar', function () {
    ($this->puntaje)();
    $c = ($this->joti)();

    expect(fn () => app(RechazarContacto::class)($this->e->dirigente, $c, '  '))->toThrow(ContactoInvalido::class);

    app(RechazarContacto::class)($this->e->dirigente, $c, 'La captura no se entiende');
    expect($c->fresh())->estado->toBe(EstadoEntrega::Rechazada)->motivo_rechazo->toBe('La captura no se entiende');

    expect(fn () => app(AprobarContacto::class)($this->e->dirigente, $c))->toThrow(ContactoYaRevisado::class)
        ->and(fn () => app(RechazarContacto::class)($this->e->dirigente, $c, 'otra vez'))->toThrow(ContactoYaRevisado::class);
    expect($this->yo->fresh()->puntos)->toBe(0);
});

// ── Cola y lista ────────────────────────────────────────────────────────────

it('la cola del dirigente muestra solo los pendientes de su unidad, y el admin elige la unidad', function () {
    $mio = ($this->joti)();
    $ya = ($this->joti)();
    app(RechazarContacto::class)($this->e->dirigente, $ya, 'no');
    $otra = escenario(1);
    app(RegistrarContactoJoti::class)($otra->miembros[0], ['grupo_scout' => 'a', 'ciudad' => 'b', 'unidad_scout' => 'c', 'pais' => 'd'], fotoFalsa());

    expect(app(ColaContactos::class)($this->e->dirigente)->pluck('id')->all())->toBe([$mio->id]);
    expect(app(ColaContactos::class)(adminDePrueba())->count())->toBe(2)
        ->and(app(ColaContactos::class)(adminDePrueba(), $otra->unidad->id)->count())->toBe(1);
    expect(fn () => app(ColaContactos::class)($this->yo))->toThrow(AuthorizationException::class);
});

it('el participante ve sus JOTI y los JOTA de su patrulla, y no los de otros', function () {
    $mio = ($this->joti)();
    $deOtroCompa = app(RegistrarContactoJoti::class)($this->e->miembros[1], ['grupo_scout' => 'a', 'ciudad' => 'b', 'unidad_scout' => 'c', 'pais' => 'd'], fotoFalsa());
    $jota = ($this->jota)([], $this->e->miembros[2]);
    $otra = escenario(1);
    ($this->jota)(['estacion' => 'LU1ABC'], $otra->miembros[0]);
    $lista = app(ContactosDelParticipante::class);

    expect(collect($lista->lista($this->yo, TipoContacto::Joti))->pluck('id')->all())->toBe([$mio->id])
        ->and(collect($lista->lista($this->yo, TipoContacto::Jota))->pluck('id')->all())->toBe([$jota->id]);

    ($this->puntaje)();
    app(AprobarContacto::class)($this->e->dirigente, $mio);
    expect($lista->resumen($this->yo))->toBe(['jota' => ['aprobados' => 0, 'pendientes' => 1], 'joti' => ['aprobados' => 1, 'pendientes' => 0]]);
});
