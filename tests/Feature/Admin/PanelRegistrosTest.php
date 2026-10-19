<?php

use App\Domain\Penalizaciones\Penalizar;
use App\Filament\Pages\RankingsCompletos;
use App\Filament\Resources\Auditoria\ActividadAuditoriaResource;
use App\Filament\Resources\Auditoria\Pages\ListAuditoria;
use App\Filament\Resources\MovimientosJlis\MovimientoJliResource;
use App\Filament\Resources\MovimientosJlis\Pages\ListMovimientosJlis;
use App\Filament\Resources\MovimientosPatrulla\MovimientoPuntoPatrullaResource;
use App\Filament\Resources\MovimientosPatrulla\Pages\ListMovimientosPatrulla;
use App\Filament\Resources\MovimientosPuntos\MovimientoPuntoIndividualResource;
use App\Filament\Resources\MovimientosPuntos\Pages\ListMovimientosPuntos;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\MovPuntoPatrulla;
use App\Models\Unidad;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->admin = adminDePrueba();
    $this->actingAs($this->admin);
    $this->e = escenario(2);
});

it('muestra los movimientos de puntos con quién, por qué y de dónde vienen', function () {
    $p = app(Penalizar::class)->persona($this->e->dirigente, $this->e->miembros[0], 15, 'Llegó tarde');
    $mov = MovPuntoIndividual::where('origen_id', $p->id)->first();

    Livewire::test(ListMovimientosPuntos::class)
        ->assertCanSeeTableRecords([$mov])
        ->assertTableColumnStateSet('origen', "Penalización #{$p->id}", $mov)
        ->assertTableColumnStateSet('registradoPor.name', $this->e->dirigente->name, $mov)
        ->assertSee('Llegó tarde')
        ->assertSee('-15');
});

it('filtra movimientos por sumas, restas y unidad', function () {
    $a = darPuntos($this->e->miembros[0], 30);
    app(Penalizar::class)->persona($this->e->dirigente, $a, 10, 'x');
    $otra = escenario(1);
    darPuntos($otra->miembros[0], 5);
    $suma = MovPuntoIndividual::where('cantidad', 30)->first();
    $resta = MovPuntoIndividual::where('cantidad', -10)->first();
    $ajeno = MovPuntoIndividual::where('cantidad', 5)->first();

    Livewire::test(ListMovimientosPuntos::class)
        ->filterTable('signo', true)->assertCanSeeTableRecords([$suma, $ajeno])->assertCanNotSeeTableRecords([$resta])
        ->filterTable('signo', false)->assertCanSeeTableRecords([$resta])->assertCanNotSeeTableRecords([$suma])
        ->removeTableFilter('signo')
        ->filterTable('unidad', $otra->unidad->id)->assertCanSeeTableRecords([$ajeno])->assertCanNotSeeTableRecords([$suma, $resta]);
});

it('busca movimientos por nombre o CI de la persona', function () {
    $ana = darPuntos($this->e->miembros[0], 30);
    darPuntos($this->e->miembros[1], 20);
    $movAna = MovPuntoIndividual::where('user_id', $ana->id)->first();
    $otro = MovPuntoIndividual::where('user_id', $this->e->miembros[1]->id)->first();

    Livewire::test(ListMovimientosPuntos::class)
        ->searchTable($ana->ci)->assertCanSeeTableRecords([$movAna])->assertCanNotSeeTableRecords([$otro]);
});

it('muestra los movimientos de patrulla y de LisCoins', function () {
    darPuntosPatrulla($this->e->patrulla, 50);
    conJlis(25, $this->e->miembros[0]);

    Livewire::test(ListMovimientosPatrulla::class)->assertCanSeeTableRecords(MovPuntoPatrulla::all())->assertSee($this->e->patrulla->nombre);
    Livewire::test(ListMovimientosJlis::class)->assertCanSeeTableRecords(MovJli::all())->assertSee('+25');
});

it('los registros contables son de solo lectura: no se crean, editan ni borran desde el panel', function (string $recurso) {
    darPuntos($this->e->miembros[0], 10);
    darPuntosPatrulla($this->e->patrulla, 10);
    conJlis(10, $this->e->miembros[1]);

    $movimiento = $recurso::getModel()::query()->firstOrFail();

    expect($recurso::canCreate())->toBeFalse()
        ->and($recurso::canEdit($movimiento))->toBeFalse()
        ->and($recurso::canDelete($movimiento))->toBeFalse()
        ->and($recurso::canDeleteAny())->toBeFalse()
        ->and(array_keys($recurso::getPages()))->toBe(['index']);
})->with([
    MovimientoPuntoIndividualResource::class,
    MovimientoPuntoPatrullaResource::class,
    MovimientoJliResource::class,
]);

it('la auditoría muestra quién cambió la configuración con el antes y el después', function () {
    $unidad = Unidad::factory()->create(['nombre' => 'Tropa']);
    $unidad->update(['nombre' => 'Tropa Scout']);
    $registro = Activity::where('event', 'updated')->latest('id')->first();

    Livewire::test(ListAuditoria::class)
        ->assertCanSeeTableRecords([$registro])
        ->assertTableColumnStateSet('causer.name', $this->admin->name, $registro)
        ->mountAction(TestAction::make('detalle')->table($registro))
        ->assertActionMounted(TestAction::make('detalle')->table($registro));

    $html = view('filament.auditoria-detalle', ['registro' => $registro])->render();
    expect($html)->toContain('Tropa Scout')->toContain('nombre')->toContain($this->admin->name);
});

it('la auditoría no permite editar ni borrar registros', function () {
    expect(ActividadAuditoriaResource::canCreate())->toBeFalse()
        ->and(ActividadAuditoriaResource::canDeleteAny())->toBeFalse();
});

// ── Recorrido de humo: ninguna página del panel debe romperse ───────────────

it('todas las páginas del panel cargan para el admin', function (string $ruta) {
    $this->get($ruta)->assertOk();
})->with([
    '/admin', '/admin/grupos', '/admin/unidades', '/admin/patrullas', '/admin/participantes', '/admin/participantes/crear',
    '/admin/importar-participantes', '/admin/misiones', '/admin/misiones/crear', '/admin/actividades', '/admin/actividades/crear',
    '/admin/entregas', '/admin/movimientos-puntos', '/admin/movimientos-patrullas', '/admin/movimientos-jlis',
    '/admin/auditoria', '/admin/rankings',
]);

it('las páginas de edición cargan', function () {
    $mision = misionPara($this->e->unidad);
    $actividad = actividadPara($this->e->unidad);
    turnoDe($actividad);

    $this->get("/admin/participantes/{$this->e->miembros[0]->id}/editar")->assertOk();
    $this->get("/admin/misiones/{$mision->id}/editar")->assertOk();
    $this->get("/admin/actividades/{$actividad->id}/editar")->assertOk()->assertSee('Turnos');
});

it('el panel está en español', function () {
    $this->get('/admin/participantes')->assertOk()->assertSee('Participantes')->assertSee('Estructura del evento');
});

it('el ranking completo del panel muestra las personas con más puntos de cada unidad por equipo', function () {
    Filament::setCurrentPanel('admin');
    $r = rankingEquipo();
    $this->actingAs($admin = adminDePrueba());

    $this->get('/admin/rankings')->assertOk()->assertSee('Personas con más puntos')->assertSee('Carla Duarte')->assertSee('Diego Escobar');
    expect(collect(Livewire::test(RankingsCompletos::class)->instance()->personasPorUnidad()['Tropa'])->first())
        ->toMatchArray(['posicion' => 1, 'puntaje' => 40]);
});
