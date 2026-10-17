<?php

use App\Domain\Actividades\AbrirSesionAsistencia;
use App\Domain\Actividades\RegistrarAsistencia;
use App\Domain\Actividades\Reservar;
use App\Domain\Actividades\TokenRotativo;
use App\Domain\Contactos\AprobarContacto;
use App\Domain\Contactos\Puntaje;
use App\Domain\Contactos\RegistrarContactoJoti;
use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Reconciliador;
use App\Domain\Misiones\AprobarEntrega;
use App\Domain\Misiones\EntregarMision;
use App\Domain\Participantes\EliminacionNoPermitida;
use App\Domain\Participantes\EliminarUsuarioDePrueba;
use App\Domain\Tienda\CrearCobro;
use App\Domain\Tienda\PagarCobro;
use App\Enums\Modalidad;
use App\Models\Contacto;
use App\Models\Entrega;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\MovPuntoPatrulla;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

/**
 * Una patrulla REAL de 4 personas con una cuenta de PRUEBA adentro (la primera). La cuenta de prueba entregó una misión por
 * patrulla (que dio puntos a la patrulla y LisCoins a todos), cargó un contacto JOTI, asistió a una actividad y reservó un turno.
 * Otra misión de un compañero real también está aprobada: eso NO se toca.
 */
beforeEach(function () {
    Storage::fake('private');
    $this->e = escenario(4);
    [$this->prueba, $this->real1, $this->real2, $this->real3] = $this->e->miembros->all();
    $this->dir = $this->e->dirigente;
    app(Puntaje::class)->guardar(['jota_local' => 10, 'jota_exterior' => 30, 'joti_local' => 5, 'joti_exterior' => 15], adminDePrueba());

    // Misión por patrulla de la cuenta de prueba (con foto) y otra de un compañero real.
    $misionPrueba = misionPara($this->e->unidad, ['modalidad' => Modalidad::Patrulla, 'puntos' => 50]);
    $entregaPrueba = app(EntregarMision::class)($this->prueba, $misionPrueba, 'hola', [fotoFalsa()]);
    app(AprobarEntrega::class)($this->dir, $entregaPrueba);

    $misionReal = misionPara($this->e->unidad, ['modalidad' => Modalidad::Patrulla, 'puntos' => 50]);
    app(AprobarEntrega::class)($this->dir, app(EntregarMision::class)($this->real1, $misionReal, 'real'));

    // Contacto JOTI de la cuenta de prueba (con captura) y asistencia + reserva.
    $contacto = app(RegistrarContactoJoti::class)($this->prueba, ['grupo_scout' => 'a', 'ciudad' => 'b', 'unidad_scout' => 'c', 'pais' => 'Paraguay'], fotoFalsa());
    app(AprobarContacto::class)($this->dir, $contacto);

    $actividad = actividadPara($this->e->unidad, ['puntos' => 20]);
    $sesion = app(AbrirSesionAsistencia::class)($this->dir, $actividad);
    app(RegistrarAsistencia::class)($this->prueba, $sesion, app(TokenRotativo::class)->generar($sesion));
    app(Reservar::class)($this->prueba, turnoDe(actividadPara($this->e->unidad), cupo: 5));

    // Y un compañero real con su propia asistencia, que tiene que quedar intacta.
    app(RegistrarAsistencia::class)($this->real1, $sesion, app(TokenRotativo::class)->generar($sesion));
});

it('antes de eliminar, los saldos están como se espera (la cuenta de prueba ensució a la patrulla)', function () {
    // Patrulla: 50 + 50. LisCoins por misión: ⌈50/4⌉ = 13 cada una → 26 a cada compañero real.
    expect($this->e->patrulla->fresh()->puntos)->toBe(100)
        ->and($this->real2->fresh()->jlis)->toBe(26)
        ->and($this->real1->fresh()->jlis)->toBe(26 + 20)->and($this->real1->fresh()->puntos)->toBe(20)
        ->and($this->prueba->fresh()->puntos)->toBe(5 + 20)->and($this->prueba->fresh()->jlis)->toBe(26 + 5 + 20);
});

it('el simulacro dice qué se borra y qué saldos se corrigen, sin cambiar nada', function () {
    $plan = app(EliminarUsuarioDePrueba::class)->plan($this->prueba);

    expect($plan['registros'])->toMatchArray(['entregas' => 1, 'asistencias' => 1, 'contactos' => 1, 'reservas' => 1, 'penalizaciones' => 0])
        ->and($plan['conflictos'])->toBe([]);

    $patrulla = collect($plan['correcciones'])->firstWhere('tipo', 'patrulla');
    expect($patrulla)->toMatchArray(['id' => $this->e->patrulla->id, 'puntos' => -50])
        ->and(collect($plan['correcciones'])->firstWhere('id', $this->real2->id))->toMatchArray(['tipo' => 'persona', 'puntos' => 0, 'liscoins' => -13]);

    expect(User::find($this->prueba->id))->not->toBeNull()->and($this->e->patrulla->fresh()->puntos)->toBe(100)->and($this->real2->fresh()->jlis)->toBe(26);
});

it('elimina la cuenta y todo lo que generó, y deja a la patrulla y a los compañeros como si nunca hubiera existido', function () {
    $archivosAntes = Storage::disk('private')->allFiles();
    expect($archivosAntes)->toHaveCount(2); // la foto de la entrega y la captura del contacto

    app(EliminarUsuarioDePrueba::class)($this->prueba);

    expect(User::find($this->prueba->id))->toBeNull()
        ->and(Entrega::count())->toBe(1)->and(Contacto::count())->toBe(0)
        ->and(DB::table('asistencias')->count())->toBe(1)->and(DB::table('reservas')->count())->toBe(0)
        ->and(DB::table('entrega_imagenes')->count())->toBe(0)
        ->and(Storage::disk('private')->allFiles())->toBe([])
        // Lo de la patrulla real: solo queda lo de la misión real (50) y sus LisCoins (13).
        ->and($this->e->patrulla->fresh()->puntos)->toBe(50)
        ->and($this->real2->fresh()->jlis)->toBe(13)->and($this->real2->fresh()->puntos)->toBe(0)
        ->and($this->real3->fresh()->jlis)->toBe(13)
        // El compañero real conserva su misión y su asistencia.
        ->and($this->real1->fresh()->jlis)->toBe(13 + 20)->and($this->real1->fresh()->puntos)->toBe(20)
        ->and(MovPuntoPatrulla::count())->toBe(1)
        ->and(MovPuntoIndividual::where('user_id', $this->prueba->id)->count())->toBe(0)
        ->and(MovJli::where('user_id', $this->prueba->id)->count())->toBe(0)
        // Y los saldos cacheados coinciden con los movimientos.
        ->and((new Reconciliador)->diferencias())->toBe([]);
});

it('se niega si un compañero ya gastó LisCoins que habría que quitarle, y no cambia nada', function () {
    app(Jlis::class)->debitar($this->real2, 20); // le quedan 6 y habría que quitarle 13

    $plan = app(EliminarUsuarioDePrueba::class)->plan($this->prueba);
    expect($plan['conflictos'])->toHaveCount(1)->and($plan['conflictos'][0])->toContain('ya gastó');

    expect(fn () => app(EliminarUsuarioDePrueba::class)($this->prueba))->toThrow(EliminacionNoPermitida::class);

    expect(User::find($this->prueba->id))->not->toBeNull()->and($this->e->patrulla->fresh()->puntos)->toBe(100)
        ->and($this->real2->fresh()->jlis)->toBe(6)->and(Entrega::count())->toBe(2);
});

it('solo elimina participantes y se niega si la cuenta tuvo actividad de tienda', function () {
    expect(fn () => app(EliminarUsuarioDePrueba::class)($this->dir))->toThrow(EliminacionNoPermitida::class, 'participantes');
    expect(fn () => app(EliminarUsuarioDePrueba::class)(adminDePrueba()))->toThrow(EliminacionNoPermitida::class);

    $cobro = app(CrearCobro::class)(usuarioTienda(), 10);
    app(PagarCobro::class)($this->prueba, $cobro->token);

    expect(fn () => app(EliminarUsuarioDePrueba::class)($this->prueba))->toThrow(EliminacionNoPermitida::class, 'cobros');
    expect(User::find($this->prueba->id))->not->toBeNull();
});

it('si los saldos ya no coincidían con los movimientos, se niega y deshace', function () {
    $this->real2->forceFill(['jlis' => 999])->save(); // saldo cacheado roto

    expect(fn () => app(EliminarUsuarioDePrueba::class)($this->prueba))->toThrow(EliminacionNoPermitida::class, 'no coinciden');

    expect(User::find($this->prueba->id))->not->toBeNull()->and($this->e->patrulla->fresh()->puntos)->toBe(100);
});

it('los movimientos siguen sin poder borrarse por el camino normal', function () {
    expect(fn () => MovJli::query()->firstOrFail()->delete())->toThrow(LogicException::class);
});

// ── Comando ─────────────────────────────────────────────────────────────────

it('el comando con --simulacro muestra el impacto y no cambia nada', function () {
    $this->artisan('jota:eliminar-usuario-de-prueba', ['ci' => $this->prueba->ci, '--simulacro' => true])
        ->expectsOutputToContain('Saldos de OTROS que se corrigen')->expectsOutputToContain('Simulacro: no se cambió nada.')->assertSuccessful();

    expect(User::find($this->prueba->id))->not->toBeNull();
});

it('el comando pide escribir el CI y cancela si no coincide', function () {
    $this->artisan('jota:eliminar-usuario-de-prueba', ['ci' => $this->prueba->ci, '--sin-respaldo' => true])
        ->expectsQuestion('Esto BORRA a '.$this->prueba->name.' y todo lo que generó, y no se puede deshacer. Escribí su CI para confirmar', '0000')
        ->expectsOutputToContain('Cancelado')->assertFailed();

    expect(User::find($this->prueba->id))->not->toBeNull();
});

it('el comando elimina con el CI correcto y deja constancia', function () {
    $this->artisan('jota:eliminar-usuario-de-prueba', ['ci' => $this->prueba->ci, '--sin-respaldo' => true])
        ->expectsQuestion('Esto BORRA a '.$this->prueba->name.' y todo lo que generó, y no se puede deshacer. Escribí su CI para confirmar', $this->prueba->ci)
        ->expectsOutputToContain('eliminado y saldos corregidos')->assertSuccessful();

    expect(User::find($this->prueba->id))->toBeNull()->and($this->e->patrulla->fresh()->puntos)->toBe(50)
        ->and(Activity::where('log_name', 'purga')->where('description', 'like', 'Eliminó una cuenta de prueba%')->count())->toBe(1);
});

it('el comando avisa si el CI no existe', function () {
    $this->artisan('jota:eliminar-usuario-de-prueba', ['ci' => '99999999', '--simulacro' => true])->expectsOutputToContain('No existe un usuario')->assertFailed();
});
