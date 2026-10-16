<?php

use App\Domain\Acceso\ResetearPin;
use App\Domain\Exportaciones\Csv;
use App\Domain\Exportaciones\Exportaciones;
use App\Enums\Rol;
use App\Models\Entrega;
use App\Models\EntregaImagen;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

// ── CSV ─────────────────────────────────────────────────────────────────────

it('escribe CSV para Excel: BOM UTF-8, separador punto y coma y tildes intactas', function () {
    $texto = Csv::contenido(['Nombre', 'Puntos'], [['Ñandú Benítez', 10], ['Ana; la "grande"', -5]]);

    expect($texto)->toStartWith("\xEF\xBB\xBF")
        ->and($texto)->toContain("Nombre;Puntos\n")
        ->and($texto)->toContain("\"Ñandú Benítez\";10\n")
        ->and($texto)->toContain('"Ana; la ""grande""";-5');
});

it('neutraliza la inyección de fórmulas en celdas de texto', function (string $peligroso) {
    expect(Csv::celda($peligroso))->toBe("'".$peligroso);
})->with(['=HYPERLINK("http://malo")', '+cmd|calc', '-2+3', '@SUM(A1)', "\tdato", "\rdato"]);

it('no altera textos normales ni números, incluso negativos', function () {
    expect(Csv::celda('Ana Benítez'))->toBe('Ana Benítez')
        ->and(Csv::celda(-15))->toBe(-15)
        ->and(Csv::celda(3.5))->toBe(3.5)
        ->and(Csv::celda(''))->toBe('');
});

it('un nombre malicioso queda neutralizado en el archivo completo', function () {
    $texto = Csv::contenido(['Nombre'], [['=1+1'], ['Normal']]);

    expect($texto)->toContain("'=1+1")->not->toContain("\n=1+1");
});

it('la descarga responde como CSV adjunto', function () {
    $respuesta = Csv::descargar('prueba.csv', ['A'], [['x']]);

    expect($respuesta->headers->get('Content-Disposition'))->toContain('prueba.csv')
        ->and($respuesta->headers->get('Content-Type'))->toContain('text/csv');
});

// ── Saldos y rankings ───────────────────────────────────────────────────────

it('exporta los saldos de todos los participantes, ordenados, sin dirigentes ni tienda', function () {
    $r = rankingEquipo();
    conJlis(40, $r->ana);
    User::factory()->rol(Rol::Tienda)->create();
    $admin = adminDePrueba();

    $filas = app(Exportaciones::class)->saldos($admin);
    $ana = collect($filas)->firstWhere(1, 'Ana Benítez');

    expect($filas)->toHaveCount(5) // los 5 participantes: sin el dirigente, la tienda ni el admin
        ->and($ana[2])->not->toBeNull()
        ->and([$ana[3], $ana[4], $ana[5], $ana[6], $ana[7]])->toBe(['Tropa', 'Águilas', 10, 40, 100])
        ->and(count(Exportaciones::ENCABEZADOS_SALDOS))->toBe(count($filas[0]));
});

it('exporta los rankings de todas las unidades con la misma cantidad de columnas', function () {
    rankingEquipo();
    $rovers = Unidad::factory()->individual()->create(['nombre' => 'Rovers']);
    darPuntos(User::factory()->create(['name' => 'Rover Uno', 'unidad_id' => $rovers->id]), 30);

    $filas = app(Exportaciones::class)->rankings(adminDePrueba());

    expect($filas)->toHaveCount(4)
        ->and($filas[0])->toBe(['Tropa', 'Equipo', 1, 'Pumas', '', 'G.S. General Aquino', 140, 50, 90, 3])
        ->and(collect($filas)->every(fn ($f) => count($f) === count(Exportaciones::ENCABEZADOS_RANKING)))->toBeTrue()
        ->and(collect($filas)->firstWhere(1, 'Individual')[3])->toBe('Rover Uno');
});

it('solo el admin exporta', function (Rol $rol) {
    $exportar = app(Exportaciones::class);
    $user = User::factory()->rol($rol)->create();

    expect(fn () => $exportar->saldos($user))->toThrow(AuthorizationException::class)
        ->and(fn () => $exportar->rankings($user))->toThrow(AuthorizationException::class);
})->with([Rol::Beneficiario, Rol::Dirigente, Rol::Tienda]);

// ── jota:purgar-entregas ────────────────────────────────────────────────────

function conFotos(): Entrega
{
    $e = escenario(1);
    $mision = misionPara($e->unidad);
    $entrega = Entrega::factory()->create(['mision_id' => $mision->id, 'user_id' => $e->miembros[0]->id, 'texto' => 'Mi respuesta']);
    Storage::disk('private')->put("entregas/{$entrega->id}/a.jpg", 'x');
    Storage::disk('private')->put("entregas/{$entrega->id}/b.jpg", 'x');
    Storage::disk('private')->put('tmp/pendiente.jpg', 'x');
    $entrega->imagenes()->createMany([['ruta' => "entregas/{$entrega->id}/a.jpg"], ['ruta' => "entregas/{$entrega->id}/b.jpg"]]);

    return $entrega;
}

it('el simulacro cuenta pero no borra', function () {
    Storage::fake('private');
    conFotos();

    $this->artisan('jota:purgar-entregas --simulacro')->expectsOutputToContain('Fotos registradas: 2')->assertSuccessful();

    expect(EntregaImagen::count())->toBe(2)->and(Storage::disk('private')->allFiles())->toHaveCount(3);
});

it('sin confirmación no borra nada', function () {
    Storage::fake('private');
    conFotos();

    $this->artisan('jota:purgar-entregas')->expectsConfirmation('Esto borra TODAS las fotos de las entregas y no se puede deshacer. ¿Seguimos?', 'no')->assertFailed();

    expect(EntregaImagen::count())->toBe(2)->and(Storage::disk('private')->allFiles())->toHaveCount(3);
});

it('borra las fotos y los archivos, pero conserva textos, revisiones y movimientos', function () {
    Storage::fake('private');
    $entrega = conFotos();
    darPuntos($entrega->user, 20);

    $this->artisan('jota:purgar-entregas')->expectsConfirmation('Esto borra TODAS las fotos de las entregas y no se puede deshacer. ¿Seguimos?', 'yes')->assertSuccessful();

    expect(EntregaImagen::count())->toBe(0)
        ->and(Storage::disk('private')->allFiles())->toBe([])
        ->and($entrega->fresh()->texto)->toBe('Mi respuesta')
        ->and($entrega->user->fresh()->puntos)->toBe(20);
});

it('--force borra sin preguntar y queda en la auditoría', function () {
    Storage::fake('private');
    conFotos();

    $this->artisan('jota:purgar-entregas --force')->assertSuccessful();

    expect(EntregaImagen::count())->toBe(0)
        ->and(Activity::where('log_name', 'purga')->first()->properties['registros'])->toBe(2);
});

it('sin fotos no hace nada', function () {
    Storage::fake('private');

    $this->artisan('jota:purgar-entregas')->expectsOutputToContain('No hay fotos')->assertSuccessful();
});

// ── Auditoría de configuración ──────────────────────────────────────────────

it('registra quién cambió la configuración, con el valor anterior y el nuevo', function () {
    $admin = adminDePrueba();
    $this->actingAs($admin);
    $unidad = Unidad::factory()->create(['nombre' => 'Tropa']);

    $unidad->update(['nombre' => 'Tropa Scout']);

    $log = Activity::where('log_name', 'configuracion')->where('event', 'updated')->latest('id')->first();
    expect($log->causer_id)->toBe($admin->id)
        ->and($log->subject_id)->toBe($unidad->id)
        ->and($log->properties['old']['nombre'])->toBe('Tropa')
        ->and($log->properties['attributes']['nombre'])->toBe('Tropa Scout');
});

it('no audita saldos, PIN ni cambios sin efecto', function () {
    $this->actingAs(adminDePrueba());
    $user = User::factory()->create();
    Activity::query()->delete();

    darPuntos($user, 50);
    conJlis(10, $user);
    $user->forceFill(['pin_hash' => '9999'])->save();
    $user->update(['name' => $user->name]);

    expect(Activity::count())->toBe(0);
});

it('los datos de la persona sí se auditan, pero nunca el PIN', function () {
    $this->actingAs(adminDePrueba());
    $user = User::factory()->create(['name' => 'Ana']);
    $user->update(['name' => 'Ana María']);

    $log = Activity::where('subject_id', $user->id)->where('event', 'updated')->first();

    expect(json_encode($log->properties, JSON_UNESCAPED_UNICODE))->toContain('Ana María')->not->toContain('pin_hash')->not->toContain('puntos');
});

it('el reseteo de PIN queda en la auditoría', function () {
    $admin = adminDePrueba();
    $user = User::factory()->create();

    app(ResetearPin::class)($admin, $user);

    $log = Activity::where('log_name', 'acceso')->first();
    expect($log->causer_id)->toBe($admin->id)->and($log->subject_id)->toBe($user->id)->and($log->description)->toBe('Reseteó el PIN');
});
