<?php

use App\Domain\Ranking\RankingUnidad;
use App\Enums\Rol;
use App\Models\Grupo;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->ranking = app(RankingUnidad::class);
    $this->admin = User::factory()->rol(Rol::Admin)->create();
});

// ── Cálculo ─────────────────────────────────────────────────────────────────

it('por equipo suma los puntos de la patrulla y los individuales de sus integrantes', function () {
    $r = rankingEquipo();

    $completo = $this->ranking->completo($this->admin, $r->unidad);

    expect(array_column($completo, 'patrulla'))->toBe(['Pumas', 'Águilas', 'Zorros'])
        ->and(array_column($completo, 'puntaje'))->toBe([140, 130, 0])
        ->and(array_column($completo, 'posicion'))->toBe([1, 2, 3])
        ->and($completo[0])->toMatchArray(['puntos_patrulla' => 50, 'puntos_individuales' => 90, 'integrantes' => 3, 'grupo' => 'G.S. General Aquino']);
});

it('los empates comparten la posición y la siguiente se salta (1, 1, 3)', function () {
    $r = rankingEquipo();
    darPuntosPatrulla($r->zorros, 130);

    $completo = $this->ranking->completo($this->admin, $r->unidad);

    expect(array_column($completo, 'puntaje'))->toBe([140, 130, 130])
        ->and(array_column($completo, 'posicion'))->toBe([1, 2, 2]);
});

it('los puntos negativos por penalización bajan el puntaje del equipo', function () {
    $r = rankingEquipo();
    darPuntosPatrulla($r->pumas, -200);

    $completo = $this->ranking->completo($this->admin, $r->unidad);

    expect($completo[2]['patrulla'])->toBe('Pumas')->and($completo[2]['puntaje'])->toBe(-60);
});

it('Rovers: ranking individual de los beneficiarios de la unidad', function () {
    $rovers = Unidad::factory()->individual()->create();
    $grupo = Grupo::factory()->create();
    $mk = fn (string $n, int $p) => darPuntos(User::factory()->create(['name' => $n, 'unidad_id' => $rovers->id, 'grupo_id' => $grupo->id]), $p);
    $mk('Rover Uno', 30);
    $mk('Rover Dos', 80);
    $mk('Rover Tres', 80);
    User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => $rovers->id, 'puntos' => 999]); // no es participante

    $completo = $this->ranking->completo($this->admin, $rovers);

    expect(array_column($completo, 'nombre'))->toBe(['Rover Dos', 'Rover Tres', 'Rover Uno'])
        ->and(array_column($completo, 'posicion'))->toBe([1, 1, 3])
        ->and(array_column($completo, 'puntaje'))->toBe([80, 80, 30]);
});

it('cada unidad tiene su ranking: no se mezclan unidades pero sí los dos grupos (D7)', function () {
    $r = rankingEquipo();
    $otra = rankingEquipo('Caminantes');
    darPuntosPatrulla($otra->zorros, 9999);

    $propio = $this->ranking->completo($this->admin, $r->unidad);

    expect(array_column($propio, 'puntaje'))->toBe([140, 130, 0])
        ->and(array_unique(array_column($propio, 'grupo')))->toHaveCount(2);
});

it('un ranking sin participantes ni patrullas está vacío', function () {
    expect($this->ranking->completo($this->admin, Unidad::factory()->individual()->create()))->toBe([]);
});

it('el cálculo usa pocas consultas sin importar el tamaño', function () {
    rankingEquipo();
    $grande = rankingEquipo('Caminantes');
    User::factory()->count(40)->create(['unidad_id' => $grande->unidad->id, 'patrulla_id' => $grande->zorros->id]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->ranking->publico($grande->unidad);
    $frio = count(DB::getQueryLog());
    DB::flushQueryLog();
    $this->ranking->publico($grande->unidad);
    $tibio = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($frio)->toBeLessThanOrEqual(2)->and($tibio)->toBe(0);
});

// ── Caché de 30 s ───────────────────────────────────────────────────────────

it('cachea 30 segundos y después refleja los cambios', function () {
    $r = rankingEquipo();
    $antes = $this->ranking->publico($r->unidad);

    darPuntosPatrulla($r->zorros, 500);
    expect($this->ranking->publico($r->unidad))->toBe($antes);

    $this->travel(29)->seconds();
    expect($this->ranking->publico($r->unidad))->toBe($antes);

    $this->travel(2)->seconds();
    expect($this->ranking->publico($r->unidad)[0]['puntaje'])->toBe(500);
});

it('la caché del admin es aparte de la pública y también dura 30 s', function () {
    $r = rankingEquipo();
    $this->ranking->publico($r->unidad);
    $this->ranking->completo($this->admin, $r->unidad);

    expect(Cache::has("ranking:publico:{$r->unidad->id}"))->toBeTrue()
        ->and(Cache::has("ranking:admin:{$r->unidad->id}"))->toBeTrue()
        ->and(RankingUnidad::TTL)->toBe(30);
});

// ── Anonimato (CLAUDE.md §2.8) ──────────────────────────────────────────────

it('la tabla pública tiene solo posicion y puntaje, y nada más', function () {
    $r = rankingEquipo();

    $tabla = $this->ranking->publico($r->unidad);

    expect($tabla)->toHaveCount(3);
    foreach ($tabla as $fila) {
        expect(array_keys($fila))->toBe(['posicion', 'puntaje']);
    }
});

it('la caché pública no guarda ningún nombre, CI ni id', function () {
    $r = rankingEquipo();
    $this->ranking->publico($r->unidad);

    $guardado = json_encode(Cache::get("ranking:publico:{$r->unidad->id}"));

    foreach (['Águilas', 'Pumas', 'Zorros', 'Ana', 'Benítez', 'General Genes', 'General Aquino', '"patrulla_id"', '"user_id"', '"ci"', '"nombre"'] as $secreto) {
        expect($guardado)->not->toContain($secreto);
    }
});

it('la vista del beneficiario trae la tabla anónima y, aparte, sus puntos y los de su patrulla', function () {
    $r = rankingEquipo();

    $vista = $this->ranking->vistaBeneficiario($r->ana);

    expect($vista['modo'])->toBe('equipo')
        ->and($vista['mis_puntos'])->toBe(10)
        ->and($vista['puntos_patrulla'])->toBe(100) // solo el registro de la patrulla, no el total del equipo
        ->and($vista['tabla'])->toBe([
            ['posicion' => 1, 'puntaje' => 140],
            ['posicion' => 2, 'puntaje' => 130],
            ['posicion' => 3, 'puntaje' => 0],
        ])
        ->and(array_keys($vista))->toBe(['modo', 'tabla', 'mis_puntos', 'puntos_patrulla', 'se_actualiza_cada']);
});

it('el payload del beneficiario no contiene nombres ni ids de otros', function () {
    $r = rankingEquipo();
    $json = json_encode($this->ranking->vistaBeneficiario($r->ana));

    foreach (['Águilas', 'Pumas', 'Zorros', 'Ana', 'Beto', 'Carla', 'Diego', 'Elena', 'Dirigente', 'General'] as $nombre) {
        expect($json)->not->toContain($nombre);
    }
    foreach ([$r->ana, $r->beto, $r->carla] as $otro) {
        expect($json)->not->toContain($otro->ci);
    }
    expect($json)->not->toContain('id"')->and($json)->not->toContain('_id');
});

it('nada marca la posición del propio equipo: la tabla es igual para todos los de la unidad', function () {
    $r = rankingEquipo();

    expect($this->ranking->vistaBeneficiario($r->ana)['tabla'])->toBe($this->ranking->vistaBeneficiario($r->carla)['tabla']);
});

it('Rovers: la vista no tiene puntos de patrulla y también es anónima', function () {
    $rovers = Unidad::factory()->individual()->create();
    $a = darPuntos(User::factory()->create(['name' => 'Rover Secreto', 'unidad_id' => $rovers->id]), 25);
    darPuntos(User::factory()->create(['name' => 'Otro Rover', 'unidad_id' => $rovers->id]), 70);

    $vista = $this->ranking->vistaBeneficiario($a);

    expect($vista['modo'])->toBe('individual')
        ->and($vista['puntos_patrulla'])->toBeNull()
        ->and($vista['mis_puntos'])->toBe(25)
        ->and($vista['tabla'])->toBe([['posicion' => 1, 'puntaje' => 70], ['posicion' => 2, 'puntaje' => 25]])
        ->and(json_encode($vista))->not->toContain('Rover');
});

it('el dirigente ve la tabla de su unidad CON los nombres de las patrullas, sin CI ni ids ni nombres de personas', function () {
    $r = rankingEquipo();

    $vista = $this->ranking->vistaDirigente($r->dirigente);

    expect($vista['tabla'])->toEqual([
        ['posicion' => 1, 'patrulla' => 'Pumas', 'grupo' => 'G.S. General Aquino', 'puntos_patrulla' => 50, 'puntos_individuales' => 90, 'integrantes' => 3, 'puntaje' => 140],
        ['posicion' => 2, 'patrulla' => 'Águilas', 'grupo' => 'G.S. General Genes', 'puntos_patrulla' => 100, 'puntos_individuales' => 30, 'integrantes' => 2, 'puntaje' => 130],
        ['posicion' => 3, 'patrulla' => 'Zorros', 'grupo' => 'G.S. General Genes', 'puntos_patrulla' => 0, 'puntos_individuales' => 0, 'integrantes' => 0, 'puntaje' => 0],
    ])->and(array_keys($vista))->toBe(['modo', 'tabla', 'se_actualiza_cada']);

    $json = json_encode($vista);
    foreach (['Ana', 'Beto', 'Carla', $r->ana->ci, 'patrulla_id', 'user_id', '"ci"'] as $privado) {
        expect($json)->not->toContain($privado);
    }
});

it('en los Rovers (individual) el dirigente ve los nombres de las personas, sin CI', function () {
    $rovers = Unidad::factory()->individual()->create(['nombre' => 'Rovers']);
    $grupo = Grupo::firstOrCreate(['nombre' => 'G.S. General Genes']);
    darPuntos(User::factory()->create(['name' => 'Rover Uno', 'unidad_id' => $rovers->id, 'patrulla_id' => null, 'grupo_id' => $grupo->id]), 30);
    $dos = darPuntos(User::factory()->create(['name' => 'Rover Dos', 'unidad_id' => $rovers->id, 'patrulla_id' => null, 'grupo_id' => $grupo->id]), 70);
    $dir = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => $rovers->id]);

    $vista = $this->ranking->vistaDirigente($dir);

    expect($vista['modo'])->toBe('individual')
        ->and($vista['tabla'])->toEqual([
            ['posicion' => 1, 'nombre' => 'Rover Dos', 'grupo' => 'G.S. General Genes', 'puntaje' => 70],
            ['posicion' => 2, 'nombre' => 'Rover Uno', 'grupo' => 'G.S. General Genes', 'puntaje' => 30],
        ])
        ->and(json_encode($vista))->not->toContain($dos->ci);
});

it('el dirigente solo ve los nombres de SU unidad, y el admin los de la unidad que elija', function () {
    $r = rankingEquipo('Tropa');
    $otra = rankingEquipo('Caminantes');

    expect(json_encode($this->ranking->vistaDirigente($otra->dirigente)))->not->toContain('Dirigente Gómez')->and(collect($this->ranking->vistaDirigente($otra->dirigente)['tabla'])->pluck('patrulla')->all())->toHaveCount(3);

    $admin = User::factory()->rol(Rol::Admin)->create();
    expect(collect($this->ranking->vistaDirigente($admin, $r->unidad->id)['tabla'])->pluck('patrulla')->all())->toBe(['Pumas', 'Águilas', 'Zorros']);
});

it('ver los nombres no contamina lo que ve el participante: su tabla y su caché siguen anónimas', function () {
    $r = rankingEquipo();

    $this->ranking->vistaDirigente($r->dirigente); // arma la caché con nombres del dirigente
    $vista = $this->ranking->vistaBeneficiario($r->ana);

    expect(json_encode($vista))->not->toContain('Águilas')->not->toContain('Pumas')
        ->and(collect($vista['tabla'])->map(fn ($fila) => array_keys($fila))->unique()->all())->toBe([['posicion', 'puntaje']])
        ->and(json_encode(Cache::get("ranking:publico:{$r->unidad->id}")))->not->toContain('Pumas')
        ->and(Cache::get("ranking:dirigente:{$r->unidad->id}"))->not->toBeNull();
});

it('cada rol recibe solo su vista: un beneficiario no pide la del dirigente y viceversa', function () {
    $r = rankingEquipo();

    expect(fn () => $this->ranking->vistaDirigente($r->ana))->toThrow(AuthorizationException::class)
        ->and(fn () => $this->ranking->vistaBeneficiario($r->dirigente))->toThrow(AuthorizationException::class)
        ->and(fn () => $this->ranking->vistaBeneficiario(User::factory()->create(['unidad_id' => null])))->toThrow(AuthorizationException::class);
});

// ── Vista completa del admin ────────────────────────────────────────────────

it('solo el admin ve el ranking completo con nombres', function () {
    $r = rankingEquipo();

    expect(fn () => $this->ranking->completo($r->dirigente, $r->unidad))->toThrow(AuthorizationException::class)
        ->and(fn () => $this->ranking->completo($r->ana, $r->unidad))->toThrow(AuthorizationException::class)
        ->and(fn () => $this->ranking->todas($r->dirigente))->toThrow(AuthorizationException::class);
});

it('el admin ve todas las unidades, por equipo e individual', function () {
    $r = rankingEquipo();
    $rovers = Unidad::factory()->individual()->create(['nombre' => 'Rovers']);
    darPuntos(User::factory()->create(['name' => 'Rover Uno', 'unidad_id' => $rovers->id]), 5);

    $todas = $this->ranking->todas($this->admin);

    expect($todas)->toHaveKeys(['Tropa', 'Rovers'])
        ->and($todas['Tropa'][0]['patrulla'])->toBe('Pumas')
        ->and($todas['Rovers'][0])->toMatchArray(['nombre' => 'Rover Uno', 'puntaje' => 5]);
});

// ── HTTP ────────────────────────────────────────────────────────────────────

it('el endpoint del ranking responde por rol y no filtra identidades', function () {
    $r = rankingEquipo();

    $respuesta = $this->actingAs($r->ana)->getJson(route('ranking'))->assertOk()
        ->assertJsonPath('modo', 'equipo')
        ->assertJsonPath('mis_puntos', 10)
        ->assertJsonCount(3, 'tabla');

    $cuerpo = $respuesta->getContent();
    foreach (['Águilas', 'Pumas', 'Zorros', 'Beto', 'Carla', 'Dirigente', $r->beto->ci, $r->carla->ci] as $secreto) {
        expect($cuerpo)->not->toContain($secreto);
    }

    // El dirigente sí recibe los nombres de las patrullas de su unidad, pero nunca CI ni ids.
    $dirigente = $this->actingAs($r->dirigente)->getJson(route('ranking'))->assertOk()->assertJsonMissingPath('mis_puntos')
        ->assertJsonPath('tabla.0.patrulla', 'Pumas');
    expect($dirigente->getContent())->not->toContain($r->ana->ci)->not->toContain('patrulla_id')->not->toContain('Beto');
});

it('el ranking pide sesión y se niega a tienda y admin (el admin usa el panel)', function () {
    $this->get(route('ranking'))->assertRedirect(route('ingresar'));

    $this->actingAs(User::factory()->rol(Rol::Tienda)->create())->getJson(route('ranking'))->assertForbidden();
    $this->actingAs($this->admin)->getJson(route('ranking'))->assertForbidden();
});
