<?php

use App\Domain\Participantes\ImportacionInvalida;
use App\Domain\Participantes\ImportarParticipantes;
use App\Enums\Rol;
use App\Models\Patrulla;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->s = estructuraImportacion();
    $this->admin = adminDePrueba();
    $this->importar = app(ImportarParticipantes::class);
});

function csv(string ...$lineas): string
{
    return implode("\n", $lineas)."\n";
}

const CABECERA = 'ci;nombre;grupo;unidad;patrulla';

it('importa participantes, crea las patrullas que faltan y los deja sin PIN', function () {
    $r = ($this->importar)($this->admin, csv(
        CABECERA,
        '1111111;Ana Benítez;General Genes;Tropa;Águilas',
        '2222222;Beto Cardozo;General Genes;Tropa;Águilas',
        '3333333;Carla Duarte;General Aquino;Tropa;Pumas',
        '4444444;Diego Escobar;General Aquino;Rovers;',
    ));

    expect($r->valido())->toBeTrue()->and($r->filas)->toBe(4)->and($r->importados)->toBe(4)
        ->and($r->patrullasNuevas)->toHaveCount(2)
        ->and(User::where('rol', Rol::Beneficiario)->count())->toBe(4)
        ->and(Patrulla::count())->toBe(2);

    $ana = User::where('ci', '1111111')->first();
    expect($ana->name)->toBe('Ana Benítez')->and($ana->pin_hash)->toBeNull()->and($ana->activado_at)->toBeNull()
        ->and($ana->patrulla->nombre)->toBe('Águilas')->and($ana->grupo->nombre)->toBe('G.S. General Genes')
        ->and(User::where('ci', '4444444')->first()->patrulla_id)->toBeNull();
    expect(User::where('ci', '2222222')->first()->patrulla_id)->toBe($ana->patrulla_id); // misma patrulla
});

it('reutiliza patrullas que ya existen sin duplicarlas', function () {
    $existente = Patrulla::factory()->create(['nombre' => 'Águilas', 'unidad_id' => $this->s->tropa->id, 'grupo_id' => $this->s->genes->id]);

    $r = ($this->importar)($this->admin, csv(CABECERA, '1111111;Ana;General Genes;Tropa;águilas'));

    expect($r->patrullasNuevas)->toBe([])->and(Patrulla::count())->toBe(1)
        ->and(User::where('ci', '1111111')->first()->patrulla_id)->toBe($existente->id);
});

it('una misma patrulla en grupos distintos son patrullas distintas', function () {
    ($this->importar)($this->admin, csv(CABECERA, '1111111;Ana;General Genes;Tropa;Águilas', '2222222;Beto;General Aquino;Tropa;Águilas'));

    expect(Patrulla::where('nombre', 'Águilas')->count())->toBe(2);
});

it('reporta el error con línea, campo y mensaje, y no importa NADA si hay alguno', function () {
    $r = ($this->importar)($this->admin, csv(
        CABECERA,
        '1111111;Ana Benítez;General Genes;Tropa;Águilas',     // línea 2: bien
        'abc;Beto;General Genes;Tropa;Águilas',                 // línea 3: CI inválido
        '3333333;;General Genes;Tropa;Águilas',                 // línea 4: sin nombre
        '4444444;Diego;Grupo Fantasma;Tropa;Águilas',           // línea 5: grupo inexistente
        '5555555;Elena;General Genes;Unidad X;Águilas',         // línea 6: unidad inexistente
        '6666666;Fabio;General Genes;Rovers;Lobos',             // línea 7: rover con patrulla
        '7777777;Gabi;General Genes;Tropa;',                    // línea 8: Tropa sin patrulla
    ));

    $porLinea = collect($r->errores)->groupBy('linea')->map(fn ($e) => $e->pluck('campo')->all());

    expect($r->valido())->toBeFalse()->and($r->importados)->toBe(0)
        ->and($porLinea->keys()->all())->toBe([3, 4, 5, 6, 7, 8])
        ->and($porLinea[3])->toBe(['ci'])->and($porLinea[4])->toBe(['nombre'])
        ->and($porLinea[5])->toBe(['grupo'])->and($porLinea[6])->toBe(['unidad'])
        ->and($porLinea[7])->toBe(['patrulla'])->and($porLinea[8])->toBe(['patrulla'])
        ->and($r->lineasConError())->toBe(6)
        ->and(User::where('rol', Rol::Beneficiario)->count())->toBe(0)
        ->and(Patrulla::count())->toBe(0); // ni siquiera las patrullas
});

it('los mensajes de grupo y unidad inexistentes listan las opciones válidas', function () {
    $r = ($this->importar)($this->admin, csv(CABECERA, '1111111;Ana;Grupo Fantasma;Unidad X;'));
    $mensajes = collect($r->errores)->pluck('mensaje', 'campo');

    expect($mensajes['grupo'])->toContain('General Genes')->toContain('General Aquino')
        ->and($mensajes['unidad'])->toContain('Tropa')->toContain('Rovers');
});

it('detecta CI repetidos dentro del archivo y dice en qué línea estaba el primero', function () {
    $r = ($this->importar)($this->admin, csv(CABECERA, '1111111;Ana;General Genes;Tropa;Águilas', '1.111.111;Ana otra vez;General Genes;Tropa;Águilas'));

    expect($r->errores)->toHaveCount(1)
        ->and($r->errores[0])->toMatchArray(['linea' => 3, 'campo' => 'ci'])
        ->and($r->errores[0]['mensaje'])->toContain('línea 2');
});

it('detecta CI que ya están cargados en la plataforma', function () {
    User::factory()->create(['ci' => '1111111']);

    $r = ($this->importar)($this->admin, csv(CABECERA, '1111111;Ana;General Genes;Tropa;Águilas'));

    expect($r->errores[0]['mensaje'])->toContain('ya está cargado')->and(User::count())->toBe(2); // el existente + el admin
});

it('el modo de validación no guarda nada pero informa lo que haría', function () {
    $r = ($this->importar)($this->admin, csv(CABECERA, '1111111;Ana;General Genes;Tropa;Águilas'), soloValidar: true);

    expect($r->valido())->toBeTrue()->and($r->simulacro)->toBeTrue()->and($r->importados)->toBe(0)
        ->and($r->patrullasNuevas)->toHaveCount(1)
        ->and(User::where('ci', '1111111')->exists())->toBeFalse()->and(Patrulla::count())->toBe(0);
});

it('acepta separador coma, punto y coma y tabulación', function (string $sep) {
    $linea = fn (...$c) => implode($sep, $c);

    $r = ($this->importar)($this->admin, csv($linea('ci', 'nombre', 'grupo', 'unidad'), $linea('1111111', 'Ana', 'General Genes', 'Rovers')));

    expect($r->importados)->toBe(1);
})->with([';', ',', "\t"]);

it('acepta encabezados con otros nombres, tildes y mayúsculas', function () {
    $r = ($this->importar)($this->admin, csv('Cédula;Nombre y Apellido;Grupo;Rama;Equipo', '1111111;Ana;General Genes;Tropa;Águilas'));

    expect($r->importados)->toBe(1);
});

it('lee archivos con BOM y comillas y CI con puntos', function () {
    $contenido = "\xEF\xBB\xBF".csv('ci;nombre;grupo;unidad', '"1.111.111";"Pérez; Ana";General Genes;Rovers');

    $r = ($this->importar)($this->admin, $contenido);

    expect($r->importados)->toBe(1)->and(User::where('ci', '1111111')->first()->name)->toBe('Pérez; Ana');
});

it('convierte archivos de Excel en Windows-1252 (tildes y eñes)', function () {
    $contenido = mb_convert_encoding(csv('ci;nombre;grupo;unidad', '1111111;Ñandú Benítez;General Genes;Rovers'), 'Windows-1252', 'UTF-8');

    ($this->importar)($this->admin, $contenido);

    expect(User::where('ci', '1111111')->first()->name)->toBe('Ñandú Benítez');
});

it('ignora líneas en blanco pero cuenta bien los números de línea', function () {
    $r = ($this->importar)($this->admin, csv('ci;nombre;grupo;unidad', '1111111;Ana;General Genes;Rovers', '', ';;;', 'abc;Beto;General Genes;Rovers'));

    expect($r->filas)->toBe(2)->and($r->errores[0]['linea'])->toBe(5);
});

it('permite cargar dirigentes y tienda, pero nunca administradores', function () {
    $r = ($this->importar)($this->admin, csv(
        'ci;nombre;grupo;unidad;rol',
        '1111111;Dirigente Uno;General Genes;Tropa;dirigente',
        '2222222;Puesto Tienda;General Genes;Rovers;tienda',
        '3333333;Quiere ser admin;General Genes;Rovers;admin',
    ));

    expect($r->valido())->toBeFalse()->and($r->errores)->toHaveCount(1)
        ->and($r->errores[0])->toMatchArray(['linea' => 4, 'campo' => 'rol'])
        ->and(User::where('rol', Rol::Admin)->count())->toBe(1); // solo el de la prueba
});

it('el rol por defecto es beneficiario', function () {
    ($this->importar)($this->admin, csv('ci;nombre;grupo;unidad', '1111111;Ana;General Genes;Rovers'));

    expect(User::where('ci', '1111111')->first()->rol)->toBe(Rol::Beneficiario);
});

it('rechaza archivos sin las columnas obligatorias, vacíos, de solo encabezado o muy grandes', function (string $contenido, string $fragmento) {
    try {
        ($this->importar)($this->admin, $contenido);
        $this->fail('Debió rechazar');
    } catch (ImportacionInvalida $e) {
        expect($e->getMessage())->toContain($fragmento);
    }
})->with([
    'sin unidad' => ["ci;nombre;grupo\n1;a;b\n", 'unidad'],
    'vacío' => ['   ', 'vacío'],
    'solo encabezado' => ["ci;nombre;grupo;unidad\n", 'no tiene participantes'],
    'muy grande' => [str_repeat('x', 2 * 1024 * 1024 + 1), '2 MB'],
]);

it('limita la cantidad de filas', function () {
    $filas = implode("\n", array_map(fn ($i) => "1{$i};N;General Genes;Rovers", range(1, 5001)));

    ($this->importar)($this->admin, "ci;nombre;grupo;unidad\n{$filas}");
})->throws(ImportacionInvalida::class, '5000');

it('solo el admin importa', function (Rol $rol) {
    ($this->importar)(User::factory()->rol($rol)->create(), csv('ci;nombre;grupo;unidad', '1111111;Ana;General Genes;Rovers'));
})->with([Rol::Beneficiario, Rol::Dirigente, Rol::Tienda])->throws(AuthorizationException::class);

it('deja registrada la importación en la auditoría', function () {
    ($this->importar)($this->admin, csv(CABECERA, '1111111;Ana;General Genes;Tropa;Águilas'));

    $log = Activity::where('log_name', 'importacion')->first();
    expect($log->causer_id)->toBe($this->admin->id)->and($log->properties['participantes'])->toBe(1);
});

it('encuentra el grupo y la unidad por coincidencia parcial única', function () {
    $r = ($this->importar)($this->admin, csv('ci;nombre;grupo;unidad', '1111111;Ana;Genes;rover', '2222222;Beto;general aquino;ROVERS'));

    expect($r->importados)->toBe(2)
        ->and(User::where('ci', '1111111')->first()->grupo->nombre)->toBe('G.S. General Genes')
        ->and(User::where('ci', '2222222')->first()->grupo->nombre)->toBe('G.S. General Aquino');
});

it('si el nombre coincide con varios grupos, lo marca ambiguo en vez de adivinar', function () {
    $r = ($this->importar)($this->admin, csv('ci;nombre;grupo;unidad', '1111111;Ana;General;Rovers'));

    expect($r->errores[0]['campo'])->toBe('grupo')->and($r->errores[0]['mensaje'])->toContain('ambiguo')
        ->and(User::where('ci', '1111111')->exists())->toBeFalse();
});

it('no adivina con textos demasiado cortos', function () {
    $r = ($this->importar)($this->admin, csv('ci;nombre;grupo;unidad', '1111111;Ana;G;Rovers'));

    expect($r->errores[0]['campo'])->toBe('grupo')->and($r->errores[0]['mensaje'])->toContain('no existe');
});
