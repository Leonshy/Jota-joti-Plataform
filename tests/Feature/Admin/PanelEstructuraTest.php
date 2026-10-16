<?php

use App\Enums\ModoRanking;
use App\Enums\Rol;
use App\Filament\Pages\ImportarParticipantes;
use App\Filament\Pages\RankingsCompletos;
use App\Filament\Resources\Grupos\Pages\ManageGrupos;
use App\Filament\Resources\Participantes\Pages\CreateParticipante;
use App\Filament\Resources\Participantes\Pages\EditParticipante;
use App\Filament\Resources\Participantes\Pages\ListParticipantes;
use App\Filament\Resources\Patrullas\Pages\ManagePatrullas;
use App\Filament\Resources\Unidades\Pages\ManageUnidades;
use App\Models\Grupo;
use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->admin = adminDePrueba();
    $this->actingAs($this->admin);
});

// ── Grupos, unidades y patrullas ────────────────────────────────────────────

it('el admin crea un grupo y no puede repetir el nombre', function () {
    Livewire::test(ManageGrupos::class)
        ->callAction('create', ['nombre' => 'G.S. General Genes'])
        ->assertHasNoFormErrors();

    Livewire::test(ManageGrupos::class)
        ->callAction('create', ['nombre' => 'G.S. General Genes'])
        ->assertHasFormErrors(['nombre' => 'unique']);

    expect(Grupo::count())->toBe(1);
});

it('un grupo con participantes no se puede borrar', function () {
    $e = escenario();
    $vacio = Grupo::factory()->create();
    $conGente = $e->patrulla->grupo;

    Livewire::test(ManageGrupos::class)
        ->assertActionHidden(TestAction::make('delete')->table($conGente))
        ->assertActionVisible(TestAction::make('delete')->table($vacio))
        ->callAction(TestAction::make('delete')->table($vacio));

    expect(Grupo::find($vacio->id))->toBeNull()->and(Grupo::find($conGente->id))->not->toBeNull();
});

it('crea una unidad con su modo de ranking', function () {
    Livewire::test(ManageUnidades::class)
        ->callAction('create', ['nombre' => 'Tropa', 'modo_ranking' => ModoRanking::Equipo->value])
        ->assertHasNoFormErrors();

    expect(Unidad::first()->modo_ranking)->toBe(ModoRanking::Equipo);
});

it('el modo de ranking de una unidad con participantes no se puede cambiar', function () {
    $e = escenario();

    Livewire::test(ManageUnidades::class)
        ->callAction(TestAction::make('edit')->table($e->unidad), ['modo_ranking' => ModoRanking::Individual->value]);

    expect($e->unidad->fresh()->modo_ranking)->toBe(ModoRanking::Equipo);
});

it('crea patrullas solo en unidades por equipo y no repite nombre en la misma unidad y grupo', function () {
    $tropa = Unidad::factory()->create();
    $rovers = Unidad::factory()->individual()->create();
    $grupo = Grupo::factory()->create();

    Livewire::test(ManagePatrullas::class)
        ->callAction('create', ['nombre' => 'Águilas', 'unidad_id' => $tropa->id, 'grupo_id' => $grupo->id])
        ->assertHasNoFormErrors();

    Livewire::test(ManagePatrullas::class)
        ->callAction('create', ['nombre' => 'Águilas', 'unidad_id' => $tropa->id, 'grupo_id' => $grupo->id])
        ->assertHasFormErrors(['nombre' => 'unique']);

    Livewire::test(ManagePatrullas::class)
        ->callAction('create', ['nombre' => 'Lobos', 'unidad_id' => $rovers->id, 'grupo_id' => $grupo->id])
        ->assertHasFormErrors(['unidad_id']);

    expect(Patrulla::count())->toBe(1);
});

it('una patrulla con integrantes no se borra', function () {
    $e = escenario();
    $vacia = Patrulla::factory()->create();

    Livewire::test(ManagePatrullas::class)
        ->assertActionHidden(TestAction::make('delete')->table($e->patrulla))
        ->assertActionVisible(TestAction::make('delete')->table($vacia));
});

// ── Participantes ───────────────────────────────────────────────────────────

it('lista a los participantes con sus saldos y permite buscar por CI y nombre', function () {
    $e = escenario(2);
    $ana = conJlis(30, darPuntos($e->miembros[0], 10));

    Livewire::test(ListParticipantes::class)
        ->assertCanSeeTableRecords($e->miembros)
        ->searchTable($ana->ci)->assertCanSeeTableRecords([$ana])->assertCanNotSeeTableRecords([$e->miembros[1]])
        ->assertTableColumnStateSet('puntos', 10, $ana)
        ->assertTableColumnStateSet('jlis', 30, $ana);
});

it('crea un participante de Tropa sin PIN, con el CI normalizado', function () {
    $e = escenario(1);

    Livewire::test(CreateParticipante::class)
        ->fillForm([
            'ci' => '1.234.567', 'name' => 'Ana Benítez', 'rol' => Rol::Beneficiario->value,
            'grupo_id' => $e->patrulla->grupo_id, 'unidad_id' => $e->unidad->id, 'patrulla_id' => $e->patrulla->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ana = User::where('ci', '1234567')->first();
    expect($ana)->not->toBeNull()->and($ana->pin_hash)->toBeNull()->and($ana->activado_at)->toBeNull()
        ->and($ana->puntos)->toBe(0)->and($ana->jlis)->toBe(0);
});

it('rechaza un participante de Tropa sin patrulla con el mismo mensaje que la importación', function () {
    $e = escenario(1);

    Livewire::test(CreateParticipante::class)
        ->fillForm(['ci' => '1234567', 'name' => 'Ana', 'rol' => Rol::Beneficiario->value, 'grupo_id' => $e->patrulla->grupo_id, 'unidad_id' => $e->unidad->id])
        ->call('create')
        ->assertHasErrors(['data.patrulla_id']);

    expect(User::where('ci', '1234567')->exists())->toBeFalse();
});

it('no deja repetir un CI', function () {
    $e = escenario(1);

    Livewire::test(CreateParticipante::class)
        ->fillForm(['ci' => $e->miembros[0]->ci, 'name' => 'Otro', 'rol' => Rol::Beneficiario->value, 'unidad_id' => $e->unidad->id, 'grupo_id' => $e->patrulla->grupo_id, 'patrulla_id' => $e->patrulla->id])
        ->call('create')
        ->assertHasFormErrors(['ci' => 'unique']);
});

it('el formulario de edición NO expone ni permite tocar puntos, LisCoins ni PIN', function () {
    $e = escenario(1);
    $yo = conJlis(50, darPuntos($e->miembros[0], 20));

    Livewire::test(EditParticipante::class, ['record' => $yo->id])
        ->assertFormFieldDoesNotExist('puntos')
        ->assertFormFieldDoesNotExist('jlis')
        ->assertFormFieldDoesNotExist('pin_hash')
        ->fillForm(['name' => 'Nombre corregido'])
        ->call('save')
        ->assertHasNoFormErrors();

    $yo->refresh();
    expect($yo->name)->toBe('Nombre corregido')->and($yo->puntos)->toBe(20)->and($yo->jlis)->toBe(50);
});

it('un admin no puede cambiar su propio rol', function () {
    Livewire::test(EditParticipante::class, ['record' => $this->admin->id])
        ->fillForm(['rol' => Rol::Beneficiario->value])
        ->call('save');

    expect($this->admin->fresh()->rol)->toBe(Rol::Admin);
});

it('resetear el PIN desde el panel borra el PIN, cierra sesiones y lo deja auditado', function () {
    $yo = escenario(1)->miembros[0];
    DB::table('sessions')->insert(['id' => 's1', 'user_id' => $yo->id, 'payload' => '', 'last_activity' => time()]);

    Livewire::test(ListParticipantes::class)
        ->callAction(TestAction::make('resetearPin')->table($yo))
        ->assertNotified();

    expect($yo->fresh()->activado_at)->toBeNull()->and($yo->fresh()->pin_hash)->toBeNull()
        ->and(DB::table('sessions')->where('user_id', $yo->id)->count())->toBe(0)
        ->and(Activity::where('description', 'Reseteó el PIN')->count())->toBe(1);
});

it('cerrar sesiones saca a la persona pero conserva su PIN', function () {
    $yo = escenario(1)->miembros[0];
    DB::table('sessions')->insert([
        ['id' => 's1', 'user_id' => $yo->id, 'payload' => '', 'last_activity' => time()],
        ['id' => 's2', 'user_id' => $yo->id, 'payload' => '', 'last_activity' => time()],
    ]);

    Livewire::test(ListParticipantes::class)->callAction(TestAction::make('cerrarSesiones')->table($yo))->assertNotified();

    expect(DB::table('sessions')->where('user_id', $yo->id)->count())->toBe(0)->and($yo->fresh()->activado_at)->not->toBeNull();
});

it('exporta los saldos desde la lista', function () {
    escenario(2);

    Livewire::test(ListParticipantes::class)->callAction('exportarSaldos')->assertFileDownloaded();
});

// ── Importar participantes ──────────────────────────────────────────────────

function archivoCsv(string $contenido): UploadedFile
{
    return UploadedFile::fake()->createWithContent('participantes.csv', $contenido);
}

it('valida el archivo sin guardar nada y muestra los errores línea por línea', function () {
    estructuraImportacion();

    Livewire::test(ImportarParticipantes::class)
        ->fillForm(['archivo' => archivoCsv("ci;nombre;grupo;unidad;patrulla\n1111111;Ana;Genes;Tropa;Águilas\nabc;Beto;Genes;Tropa;Águilas\n")])
        ->call('validar')
        ->assertSet('resultado.valido', false)
        ->assertSet('resultado.errores.0.linea', 3)
        ->assertSee('Qué pasó')
        ->assertSee('El CI debe tener solo números');

    expect(User::where('ci', '1111111')->exists())->toBeFalse();
});

it('valida un archivo correcto y recién al importar lo guarda', function () {
    estructuraImportacion();
    $csv = "ci;nombre;grupo;unidad;patrulla\n1111111;Ana;Genes;Tropa;Águilas\n2222222;Beto;Aquino;Rovers;\n";

    Livewire::test(ImportarParticipantes::class)
        ->fillForm(['archivo' => archivoCsv($csv)])
        ->call('validar')
        ->assertSet('resultado.valido', true)->assertSet('resultado.simulacro', true)
        ->assertSee('Todavía no se guardó nada');
    expect(User::where('rol', Rol::Beneficiario)->count())->toBe(0);

    Livewire::test(ImportarParticipantes::class)
        ->fillForm(['archivo' => archivoCsv($csv)])
        ->call('importar')
        ->assertNotified()
        ->assertSet('resultado.importados', 2);
    expect(User::where('rol', Rol::Beneficiario)->count())->toBe(2);
});

it('importar un archivo con errores no carga nada', function () {
    estructuraImportacion();

    Livewire::test(ImportarParticipantes::class)
        ->fillForm(['archivo' => archivoCsv("ci;nombre;grupo;unidad;patrulla\n1111111;Ana;Genes;Tropa;Águilas\n2222222;Beto;Fantasma;Tropa;Águilas\n")])
        ->call('importar')
        ->assertSet('resultado.importados', 0)->assertSee('No se cargó nada');

    expect(User::where('rol', Rol::Beneficiario)->count())->toBe(0);
});

it('avisa cuando el archivo no se puede leer', function () {
    Livewire::test(ImportarParticipantes::class)
        ->fillForm(['archivo' => archivoCsv("ci;nombre\n1;a\n")])
        ->call('validar')
        ->assertSee('Faltan columnas');
});

it('exige elegir un archivo', function () {
    Livewire::test(ImportarParticipantes::class)->call('validar')->assertHasFormErrors(['archivo' => 'required']);
});

it('descarga la plantilla con los nombres reales de grupo y unidad', function () {
    estructuraImportacion();

    Livewire::test(ImportarParticipantes::class)->callAction('plantilla')->assertFileDownloaded('plantilla-participantes.csv');
});

it('solo el admin ve las páginas de importación y rankings', function () {
    auth()->logout();
    $this->actingAs(User::factory()->rol(Rol::Dirigente)->create());

    expect(ImportarParticipantes::canAccess())->toBeFalse()->and(RankingsCompletos::canAccess())->toBeFalse();
});

// ── Rankings completos ──────────────────────────────────────────────────────

it('muestra los rankings completos con nombres y desglose', function () {
    rankingEquipo();

    Livewire::test(RankingsCompletos::class)
        ->assertSee('Tropa')->assertSee('Pumas')->assertSee('Águilas')->assertSee('General Aquino')
        ->assertSee('140')->assertSee('130');
});

it('exporta rankings y saldos desde la página', function () {
    rankingEquipo();

    Livewire::test(RankingsCompletos::class)->callAction('exportarRankings')->assertFileDownloaded();
    Livewire::test(RankingsCompletos::class)->callAction('exportarSaldos')->assertFileDownloaded();
});
