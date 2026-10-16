<?php

use App\Domain\Actividades\AbrirSesionAsistencia;
use App\Domain\Actividades\RegistrarAsistencia;
use App\Domain\Actividades\Reservar;
use App\Domain\Actividades\TokenRotativo;
use App\Domain\Misiones\AprobarEntrega;
use App\Domain\Misiones\EntregarMision;
use App\Enums\EstadoEntrega;
use App\Enums\Modalidad;
use App\Enums\TipoActividad;
use App\Filament\Resources\Actividades\Pages\CreateActividad;
use App\Filament\Resources\Actividades\Pages\EditActividad;
use App\Filament\Resources\Actividades\Pages\ListActividades;
use App\Filament\Resources\Actividades\RelationManagers\TurnosRelationManager;
use App\Filament\Resources\Entregas\Pages\ListEntregas;
use App\Filament\Resources\Misiones\Pages\CreateMision;
use App\Filament\Resources\Misiones\Pages\EditMision;
use App\Filament\Resources\Misiones\Pages\ListMisiones;
use App\Models\Actividad;
use App\Models\Entrega;
use App\Models\Mision;
use App\Models\MovJli;
use App\Models\MovPuntoIndividual;
use App\Models\Reserva;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->admin = adminDePrueba();
    $this->actingAs($this->admin);
    $this->e = escenario(3);
});

// ── Misiones ────────────────────────────────────────────────────────────────

it('crea una misión con sus unidades, puntaje y horarios', function () {
    Livewire::test(CreateMision::class)
        ->fillForm([
            'titulo' => 'Nudos en equipo', 'lugar' => 'Campo de patrullas', 'descripcion' => 'Armen un nudo de ocho.',
            'modalidad' => Modalidad::Patrulla->value, 'puntos' => 50,
            'unidades' => [$this->e->unidad->id],
            'lanza_at' => now()->addHour()->format('Y-m-d H:i'), 'vence_at' => now()->addHours(5)->format('Y-m-d H:i'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $mision = Mision::first();
    expect($mision->titulo)->toBe('Nudos en equipo')->and($mision->puntos)->toBe(50)
        ->and($mision->unidades->pluck('id')->all())->toBe([$this->e->unidad->id])
        ->and($mision->estado())->toBe('programada');
});

it('valida la misión: puntaje positivo, unidades y vencimiento posterior al lanzamiento', function () {
    Livewire::test(CreateMision::class)
        ->fillForm([
            'titulo' => 'X', 'lugar' => 'Z', 'descripcion' => 'Y', 'modalidad' => Modalidad::Individual->value, 'puntos' => 0,
            'unidades' => [], 'lanza_at' => now()->addHours(3)->format('Y-m-d H:i'), 'vence_at' => now()->addHour()->format('Y-m-d H:i'),
        ])
        ->call('create')
        ->assertHasFormErrors(['puntos', 'unidades', 'vence_at']);

    expect(Mision::count())->toBe(0);
});

it('lista las misiones con su estado y las entregas por revisar', function () {
    $abierta = misionPara($this->e->unidad);
    $futura = misionPara($this->e->unidad, [], 'futura');
    $vencida = misionPara($this->e->unidad, [], 'vencida');
    Entrega::factory()->create(['mision_id' => $abierta->id, 'user_id' => $this->e->miembros[0]->id]);

    Livewire::test(ListMisiones::class)
        ->assertCanSeeTableRecords([$abierta, $futura, $vencida])
        ->assertTableColumnStateSet('estado', 'abierta', $abierta)
        ->assertTableColumnStateSet('estado', 'programada', $futura)
        ->assertTableColumnStateSet('estado', 'vencida', $vencida)
        ->assertTableColumnStateSet('pendientes_count', 1, $abierta);
});

it('cierra una misión a mano desde la tabla', function () {
    $mision = misionPara($this->e->unidad);

    Livewire::test(ListMisiones::class)
        ->callAction(TestAction::make('cerrar')->table($mision))
        ->assertNotified();

    expect($mision->fresh()->estado())->toBe('cerrada');
});

it('no ofrece cerrar una misión ya cerrada o vencida', function () {
    $cerrada = misionPara($this->e->unidad, [], 'cerrada');
    $vencida = misionPara($this->e->unidad, [], 'vencida');

    Livewire::test(ListMisiones::class)
        ->assertActionHidden(TestAction::make('cerrar')->table($cerrada))
        ->assertActionHidden(TestAction::make('cerrar')->table($vencida));
});

it('una misión con entregas no se borra ni cambia de puntaje o modalidad', function () {
    $mision = misionPara($this->e->unidad, ['puntos' => 50]);
    Entrega::factory()->create(['mision_id' => $mision->id, 'user_id' => $this->e->miembros[0]->id]);
    $sinEntregas = misionPara($this->e->unidad);

    Livewire::test(ListMisiones::class)
        ->assertActionHidden(TestAction::make('delete')->table($mision))
        ->assertActionVisible(TestAction::make('delete')->table($sinEntregas));

    Livewire::test(EditMision::class, ['record' => $mision->id])
        ->fillForm(['titulo' => 'Título nuevo', 'puntos' => 999, 'modalidad' => Modalidad::Patrulla->value])
        ->call('save');

    $mision->refresh();
    expect($mision->titulo)->toBe('Título nuevo')->and($mision->puntos)->toBe(50)->and($mision->modalidad)->toBe(Modalidad::Individual);
});

// ── Actividades y turnos ────────────────────────────────────────────────────

it('crea una actividad que puede valer 0 puntos', function () {
    Livewire::test(CreateActividad::class)
        ->fillForm([
            'titulo' => 'Estación de radioaficionados', 'lugar' => 'Carpa de radio', 'tipo' => TipoActividad::EstacionJota->value,
            'modalidad' => Modalidad::Individual->value, 'puntos' => 0, 'unidades' => [$this->e->unidad->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Actividad::first())->puntos->toBe(0)->tipo->toBe(TipoActividad::EstacionJota);
});

it('lista las actividades con su cantidad de turnos', function () {
    $actividad = actividadPara($this->e->unidad);
    turnoDe($actividad);
    turnoDe($actividad, '+3 hours', '+4 hours');

    Livewire::test(ListActividades::class)->assertCanSeeTableRecords([$actividad])->assertTableColumnStateSet('turnos_count', 2, $actividad);
});

it('crea turnos con horario y cupo desde la actividad, y valida que termine después de empezar', function () {
    $actividad = actividadPara($this->e->unidad);

    Livewire::test(TurnosRelationManager::class, ['ownerRecord' => $actividad, 'pageClass' => EditActividad::class])
        ->callAction(TestAction::make('create')->table(), [
            'inicio' => now()->addHour()->format('Y-m-d H:i'), 'fin' => now()->addHours(2)->format('Y-m-d H:i'), 'cupo' => 8,
        ])
        ->assertHasNoFormErrors();

    Livewire::test(TurnosRelationManager::class, ['ownerRecord' => $actividad, 'pageClass' => EditActividad::class])
        ->callAction(TestAction::make('create')->table(), [
            'inicio' => now()->addHours(3)->format('Y-m-d H:i'), 'fin' => now()->addHours(2)->format('Y-m-d H:i'),
        ])
        ->assertHasFormErrors(['fin']);

    expect($actividad->turnos()->count())->toBe(1)->and($actividad->turnos()->first()->cupo)->toBe(8);
});

it('un turno con reservas no se borra ni se le mueve el horario', function () {
    $actividad = actividadPara($this->e->unidad);
    $conReservas = turnoDe($actividad, '+1 hour', '+2 hours');
    $libre = turnoDe($actividad, '+3 hours', '+4 hours');
    app(Reservar::class)($this->e->miembros[0], $conReservas);

    Livewire::test(TurnosRelationManager::class, ['ownerRecord' => $actividad, 'pageClass' => EditActividad::class])
        ->assertActionHidden(TestAction::make('delete')->table($conReservas))
        ->assertActionVisible(TestAction::make('delete')->table($libre))
        ->callAction(TestAction::make('edit')->table($conReservas), [
            'inicio' => now()->addHours(10)->format('Y-m-d H:i'), 'fin' => now()->addHours(11)->format('Y-m-d H:i'), 'cupo' => 20,
        ]);

    $conReservas->refresh();
    expect($conReservas->cupo)->toBe(20)
        ->and($conReservas->inicio->format('H'))->toBe(now()->addHour()->format('H')) // el horario no se movió
        ->and(Reserva::count())->toBe(1);
});

it('una actividad con asistencias no cambia de puntaje ni modalidad, ni se borra', function () {
    $actividad = actividadPara($this->e->unidad, ['puntos' => 20]);
    $sesion = app(AbrirSesionAsistencia::class)($this->e->dirigente, $actividad);
    app(RegistrarAsistencia::class)($this->e->miembros[0], $sesion, app(TokenRotativo::class)->generar($sesion));

    Livewire::test(ListActividades::class)->assertActionHidden(TestAction::make('delete')->table($actividad));

    Livewire::test(EditActividad::class, ['record' => $actividad->id])
        ->fillForm(['titulo' => 'Nuevo título', 'puntos' => 500, 'modalidad' => Modalidad::Patrulla->value])
        ->call('save');

    $actividad->refresh();
    expect($actividad->titulo)->toBe('Nuevo título')->and($actividad->puntos)->toBe(20)->and($actividad->modalidad)->toBe(Modalidad::Individual);
});

// ── Validación global de entregas ───────────────────────────────────────────

function entregaDe($e, int $quien = 0, array $mision = []): Entrega
{
    $m = misionPara($e->unidad, $mision);

    return Entrega::factory()->create(['mision_id' => $m->id, 'user_id' => $e->miembros[$quien]->id, 'texto' => 'Mi respuesta']);
}

it('el admin ve las entregas pendientes de cualquier unidad, las más viejas primero', function () {
    $otra = escenario(1);
    $vieja = entregaDe($this->e);
    $this->travel(5)->minutes();
    $nueva = entregaDe($otra);

    Livewire::test(ListEntregas::class)->assertCanSeeTableRecords([$vieja, $nueva], inOrder: true);
});

it('por defecto muestra solo las pendientes y se puede cambiar el filtro', function () {
    $pendiente = entregaDe($this->e, 0);
    $aprobada = entregaDe($this->e, 1);
    app(AprobarEntrega::class)($this->admin, $aprobada);

    Livewire::test(ListEntregas::class)
        ->assertCanSeeTableRecords([$pendiente])->assertCanNotSeeTableRecords([$aprobada])
        ->filterTable('estado', EstadoEntrega::Aprobada->value)
        ->assertCanSeeTableRecords([$aprobada])->assertCanNotSeeTableRecords([$pendiente]);
});

it('aprueba desde el panel y acredita puntos y LisCoins', function () {
    $entrega = entregaDe($this->e, 0, ['puntos' => 40]);

    Livewire::test(ListEntregas::class)->callAction(TestAction::make('aprobar')->table($entrega))->assertNotified();

    $yo = $this->e->miembros[0]->fresh();
    expect($entrega->fresh()->estado)->toBe(EstadoEntrega::Aprobada)
        ->and($yo->puntos)->toBe(40)->and($yo->jlis)->toBe(40)
        ->and($entrega->fresh()->revisado_por)->toBe($this->admin->id);
});

it('aprobar una entrega ya aprobada avisa y no acredita otra vez', function () {
    $entrega = entregaDe($this->e);
    app(AprobarEntrega::class)($this->admin, $entrega);

    Livewire::test(ListEntregas::class)
        ->filterTable('estado', EstadoEntrega::Aprobada->value)
        ->assertActionHidden(TestAction::make('aprobar')->table($entrega));

    expect(MovPuntoIndividual::count())->toBe(1)->and(MovJli::count())->toBe(1);
});

it('rechaza desde el panel con motivo obligatorio', function () {
    $entrega = entregaDe($this->e);

    Livewire::test(ListEntregas::class)
        ->callAction(TestAction::make('rechazar')->table($entrega), ['motivo' => ''])
        ->assertHasFormErrors(['motivo' => 'required']);
    expect($entrega->fresh()->estado)->toBe(EstadoEntrega::Pendiente);

    Livewire::test(ListEntregas::class)
        ->callAction(TestAction::make('rechazar')->table($entrega), ['motivo' => 'La foto está borrosa'])
        ->assertNotified();

    expect($entrega->fresh()->estado)->toBe(EstadoEntrega::Rechazada)->and($entrega->fresh()->motivo_rechazo)->toBe('La foto está borrosa')
        ->and(MovPuntoIndividual::count())->toBe(0);
});

it('el detalle muestra el texto y las fotos procesadas a través de la ruta con Policy', function () {
    Storage::fake('private');
    $entrega = app(EntregarMision::class)($this->e->miembros[0], misionPara($this->e->unidad), 'Texto de la entrega', [fotoFalsa()]);
    $imagen = $entrega->imagenes()->first();

    Livewire::test(ListEntregas::class)->mountAction(TestAction::make('ver')->table($entrega))->assertActionMounted(TestAction::make('ver')->table($entrega));

    $html = view('filament.entrega-detalle', ['entrega' => $entrega->load(['user.unidad', 'patrulla', 'imagenes', 'revisor'])])->render();

    expect($html)->toContain('Texto de la entrega')
        ->toContain(route('entregas.imagen', $imagen))
        ->toContain($this->e->miembros[0]->name);
});

it('el detalle avisa cuando una foto todavía no se procesó, sin mostrar el original', function () {
    $entrega = entregaDe($this->e);
    $entrega->imagenes()->create(['ruta' => 'tmp/original-con-gps.jpg']);

    $html = view('filament.entrega-detalle', ['entrega' => $entrega->load(['user.unidad', 'patrulla', 'imagenes', 'revisor'])])->render();

    expect($html)->toContain('no está disponible')->not->toContain('<img')->not->toContain('original-con-gps');
});

it('el texto de una entrega se muestra escapado (sin ejecutar HTML)', function () {
    $entrega = entregaDe($this->e);
    $entrega->update(['texto' => '<script>alert("xss")</script>', 'motivo_rechazo' => '<b>x</b>']);

    $html = view('filament.entrega-detalle', ['entrega' => $entrega->load(['user.unidad', 'patrulla', 'imagenes', 'revisor'])])->render();

    expect($html)->not->toContain('<script>')->toContain('&lt;script&gt;')->not->toContain('<b>x</b>');
});

it('un dirigente no accede a la validación global', function () {
    auth()->logout();

    $this->actingAs($this->e->dirigente)->get('/admin/entregas')->assertForbidden();
});
