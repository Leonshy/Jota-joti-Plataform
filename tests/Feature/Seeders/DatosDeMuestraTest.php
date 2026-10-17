<?php

use App\Domain\Actividades\AgendaDelParticipante;
use App\Domain\Contactos\ColaContactos;
use App\Domain\Ledger\Reconciliador;
use App\Domain\Misiones\ColaValidacion;
use App\Domain\Misiones\MisionesDelParticipante;
use App\Models\Ajuste;
use App\Models\Contacto;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DatosDeMuestraSeeder;

// El cargador de datos de muestra del entorno local tiene que seguir funcionando a medida que cambia el sistema.

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatosDeMuestraSeeder::class);
    $this->aguilas1 = User::where('ci', '4000001')->firstOrFail();
});

it('carga los datos de muestra sin romper la contabilidad', function () {
    expect((new Reconciliador)->diferencias())->toBe([])
        ->and(Ajuste::where('clave', 'datos_de_muestra')->exists())->toBeTrue()
        ->and(Contacto::count())->toBe(10);
});

it('Águilas 1 ve las misiones en el orden pedido y con los seis estados', function () {
    $estados = collect(app(MisionesDelParticipante::class)->lista($this->aguilas1))->pluck('estado')->unique()->values()->all();

    expect($estados)->toBe(['rechazada', 'nueva', 'pendiente', 'aprobada', 'vencida', 'cerrada']);
});

it('Águilas 1 ve talleres en curso, con lugar, apuntado y lleno, y estaciones disponibles', function () {
    $agenda = collect(app(AgendaDelParticipante::class)->actividades($this->aguilas1));
    $talleres = $agenda->where('taller', true);

    expect($talleres->pluck('titulo')->first())->toBe('IA para tus ideas')
        ->and($talleres->flatMap(fn ($a) => collect($a['turnos'])->pluck('estado'))->unique()->sort()->values()->all())->toBe(['apuntado', 'con_lugar', 'en_curso', 'lleno'])
        ->and($agenda->where('taller', false)->flatMap(fn ($a) => collect($a['turnos'])->pluck('estado'))->unique()->all())->toBe(['disponible']);

    // Pumas tiene una estación reservada y en curso: es la única que ve.
    $pumas = collect(app(AgendaDelParticipante::class)->actividades(User::where('ci', '4000004')->firstOrFail()))->where('taller', false);
    expect($pumas->pluck('titulo')->all())->toBe(['Estación JOTA: brújula y mapa'])
        ->and($pumas->first()['turnos'][0]['estado'])->toBe('en_curso');
});

it('el dirigente de la Tropa tiene entregas y contactos por revisar', function () {
    $dir = User::where('ci', '4000045')->firstOrFail();

    expect(app(ColaValidacion::class)($dir)->count())->toBeGreaterThan(0)
        ->and(app(ColaContactos::class)($dir)->count())->toBeGreaterThan(0);
});

it('no se carga dos veces, y nunca en producción', function () {
    $antes = Contacto::count();
    $this->seed(DatosDeMuestraSeeder::class);
    expect(Contacto::count())->toBe($antes);

    app()->detectEnvironment(fn () => 'production');
    expect(fn () => (new DatosDeMuestraSeeder)->run())->toThrow(RuntimeException::class, 'producción');
});
