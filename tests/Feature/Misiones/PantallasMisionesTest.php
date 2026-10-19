<?php

use App\Domain\Misiones\AprobarEntrega;
use App\Domain\Misiones\MisionesDelParticipante;
use App\Enums\EstadoEntrega;
use App\Enums\Modalidad;
use App\Livewire\Beneficiario\Entregar;
use App\Livewire\Dirigente\Cola;
use App\Livewire\Dirigente\Revisar;
use App\Models\Entrega;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('private');
    $this->e = escenario(3);
    $this->yo = $this->e->miembros[0];
});

// ── Lista y estados ─────────────────────────────────────────────────────────

it('la lista muestra las misiones lanzadas de mi unidad y oculta futuras y de otras unidades', function () {
    misionPara($this->e->unidad, ['titulo' => 'Visible']);
    misionPara($this->e->unidad, ['titulo' => 'Futura'], 'futura');
    misionPara(Unidad::factory()->create(), ['titulo' => 'Ajena']);

    $this->actingAs($this->yo)->get(route('app.misiones'))
        ->assertOk()->assertSee('Visible')->assertDontSee('Futura')->assertDontSee('Ajena');
});

it('calcula el estado de cada misión para quien mira', function () {
    $nueva = misionPara($this->e->unidad);
    $vencida = misionPara($this->e->unidad, [], 'vencida');
    $cerrada = misionPara($this->e->unidad, [], 'cerrada');
    $pendiente = misionPara($this->e->unidad);
    $aprobada = misionPara($this->e->unidad);
    $rechazada = misionPara($this->e->unidad);
    Entrega::factory()->create(['mision_id' => $pendiente->id, 'user_id' => $this->yo->id, 'estado' => EstadoEntrega::Pendiente]);
    Entrega::factory()->create(['mision_id' => $aprobada->id, 'user_id' => $this->yo->id, 'estado' => EstadoEntrega::Aprobada]);
    Entrega::factory()->create(['mision_id' => $rechazada->id, 'user_id' => $this->yo->id, 'estado' => EstadoEntrega::Rechazada, 'motivo_rechazo' => 'Borrosa']);

    $estados = collect(app(MisionesDelParticipante::class)->lista($this->yo))->pluck('estado', 'id');

    expect($estados[$nueva->id])->toBe('nueva')
        ->and($estados[$vencida->id])->toBe('vencida')
        ->and($estados[$cerrada->id])->toBe('cerrada')
        ->and($estados[$pendiente->id])->toBe('pendiente')
        ->and($estados[$aprobada->id])->toBe('aprobada')
        ->and($estados[$rechazada->id])->toBe('rechazada');
});

it('una misión por patrulla refleja la entrega de cualquier integrante', function () {
    $m = misionPara($this->e->unidad, ['modalidad' => Modalidad::Patrulla]);
    Entrega::factory()->create(['mision_id' => $m->id, 'user_id' => $this->e->miembros[1]->id, 'patrulla_id' => $this->e->patrulla->id, 'estado' => EstadoEntrega::Pendiente]);

    $this->actingAs($this->yo)->get(route('app.mision', $m))->assertOk()
        ->assertSee(__('jota.misiones.patrulla_entrego', ['nombre' => strtok($this->e->miembros[1]->name, ' ')]));
    expect(app(MisionesDelParticipante::class)->detalle($this->yo, $m)['puede_entregar'])->toBeFalse();
});

it('no deja ver el detalle de una misión futura ni de otra unidad', function () {
    $futura = misionPara($this->e->unidad, [], 'futura');
    $ajena = misionPara(Unidad::factory()->create());

    $this->actingAs($this->yo)->get(route('app.mision', $futura))->assertForbidden();
    $this->actingAs($this->yo)->get(route('app.mision', $ajena))->assertForbidden();
    $this->actingAs($this->yo)->get(route('app.entregar', $ajena))->assertForbidden();
});

it('la lista usa pocas consultas aunque haya muchas misiones', function () {
    foreach (range(1, 25) as $_) {
        misionPara($this->e->unidad);
    }

    DB::enableQueryLog();
    app(MisionesDelParticipante::class)->lista($this->yo);

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(4);
});

it('un dirigente no entra a las pantallas del participante', function () {
    $this->actingAs($this->e->dirigente)->get(route('app.misiones'))->assertForbidden();
});

// ── Entregar ────────────────────────────────────────────────────────────────

it('entrega con texto y fotos y muestra la confirmación', function () {
    $mision = misionPara($this->e->unidad);

    Livewire::actingAs($this->yo)->test(Entregar::class, ['mision' => $mision])
        ->set('nueva', fotoFalsa('a.jpg'))->set('nueva', fotoFalsa('b.png'))
        ->assertCount('fotos', 2)
        ->set('texto', 'Soy Ana')->call('enviar')
        ->assertHasNoErrors()->assertSet('enviada', true)->assertSee(__('jota.misiones.enviada_titulo'));

    $entrega = Entrega::sole();
    expect($entrega->estado)->toBe(EstadoEntrega::Pendiente)->and($entrega->texto)->toBe('Soy Ana')
        ->and($entrega->imagenes)->toHaveCount(2)->and($entrega->user_id)->toBe($this->yo->id);
});

it('no deja entregar vacío ni con más de 4 fotos, y se pueden quitar fotos', function () {
    $mision = misionPara($this->e->unidad);
    $c = Livewire::actingAs($this->yo)->test(Entregar::class, ['mision' => $mision]);

    $c->call('enviar')->assertHasErrors(['texto']);

    foreach (range(1, 5) as $n) {
        $c->set('nueva', fotoFalsa("f{$n}.jpg"));
    }
    $c->assertCount('fotos', 4)->assertHasErrors(['fotos']);

    $c->call('quitar', 0)->assertCount('fotos', 3);
    expect(Entrega::count())->toBe(0);
});

it('rechaza un archivo que no es una foto', function () {
    $mision = misionPara($this->e->unidad);
    $falso = UploadedFile::fake()->createWithContent('virus.jpg', '<?php echo 1;');

    Livewire::actingAs($this->yo)->test(Entregar::class, ['mision' => $mision])
        ->set('nueva', $falso)->assertCount('fotos', 0)->assertHasErrors(['fotos']);
});

it('avisa si la misión venció o si ya hay una entrega pendiente', function () {
    $mision = misionPara($this->e->unidad);
    $c = Livewire::actingAs($this->yo)->test(Entregar::class, ['mision' => $mision])->set('texto', 'uno');
    $mision->update(['vence_at' => now()->subMinute()]);
    $c->call('enviar')->assertHasErrors(['texto'])->assertSet('enviada', false);

    $otra = misionPara($this->e->unidad);
    Entrega::factory()->create(['mision_id' => $otra->id, 'user_id' => $this->yo->id, 'estado' => EstadoEntrega::Pendiente]);
    Livewire::actingAs($this->yo)->test(Entregar::class, ['mision' => $otra])->set('texto', 'dos')->call('enviar')->assertHasErrors(['texto']);
});

it('una misión por patrulla la entrega cualquier integrante; sin patrulla no se puede', function () {
    $mision = misionPara($this->e->unidad, ['modalidad' => Modalidad::Patrulla]);

    Livewire::actingAs($this->yo)->test(Entregar::class, ['mision' => $mision])->set('texto', 'Equipo')->call('enviar')->assertSet('enviada', true);
    expect(Entrega::sole()->patrulla_id)->toBe($this->e->patrulla->id);

    $sin = User::factory()->create(['unidad_id' => $this->e->unidad->id]);
    Livewire::actingAs($sin)->test(Entregar::class, ['mision' => $mision])->set('texto', 'x')->call('enviar')->assertHasErrors(['texto']);
});

// ── Cola y revisión del dirigente ───────────────────────────────────────────

it('la cola solo muestra entregas de la unidad del dirigente, las más antiguas primero', function () {
    $propia = misionPara($this->e->unidad, ['titulo' => 'Mision propia']);
    $otra = escenario(1);
    $ajena = misionPara($otra->unidad, ['titulo' => 'Mision ajena']);
    Entrega::factory()->create(['mision_id' => $propia->id, 'user_id' => $this->yo->id, 'created_at' => now()->subHour()]);
    Entrega::factory()->create(['mision_id' => $propia->id, 'user_id' => $this->e->miembros[1]->id, 'created_at' => now()->subMinutes(5)]);
    Entrega::factory()->create(['mision_id' => $ajena->id, 'user_id' => $otra->miembros[0]->id]);

    $this->actingAs($this->e->dirigente)->get(route('dirigente.inicio'))
        ->assertOk()->assertSeeInOrder([$this->yo->name, $this->e->miembros[1]->name])
        ->assertSee('Mision propia')->assertDontSee('Mision ajena')->assertDontSee($otra->miembros[0]->name);
});

it('la cola vacía lo dice', function () {
    Livewire::actingAs($this->e->dirigente)->test(Cola::class)->assertSee(__('jota.dirigente.cola_vacia_titulo'));
});

it('aprobar acredita puntos y LisCoins y muestra el resultado', function () {
    $mision = misionPara($this->e->unidad, ['puntos' => 40]);
    $entrega = Entrega::factory()->create(['mision_id' => $mision->id, 'user_id' => $this->yo->id]);

    Livewire::actingAs($this->e->dirigente)->test(Revisar::class, ['entrega' => $entrega])
        ->call('aprobar')->assertSet('paso', 'aprobada')->assertSee(__('jota.dirigente.aprobada_titulo'));

    expect($this->yo->fresh())->puntos->toBe(40)->jlis->toBe(40)
        ->and($entrega->fresh()->estado)->toBe(EstadoEntrega::Aprobada);
});

it('aprobar una misión por patrulla reparte los LisCoins entre los integrantes', function () {
    $mision = misionPara($this->e->unidad, ['puntos' => 50, 'modalidad' => Modalidad::Patrulla]);
    $entrega = Entrega::factory()->create(['mision_id' => $mision->id, 'user_id' => $this->yo->id, 'patrulla_id' => $this->e->patrulla->id]);

    Livewire::actingAs($this->e->dirigente)->test(Revisar::class, ['entrega' => $entrega])
        ->assertSee('17') // ⌈50/3⌉ LisCoins por integrante, antes de aprobar
        ->call('aprobar')->assertSet('paso', 'aprobada');

    expect($this->e->patrulla->fresh()->puntos)->toBe(50)
        ->and($this->e->miembros->every(fn ($m) => $m->fresh()->jlis === 17))->toBeTrue();
});

it('rechazar exige motivo y lo guarda', function () {
    $entrega = Entrega::factory()->create(['mision_id' => misionPara($this->e->unidad)->id, 'user_id' => $this->yo->id]);

    $c = Livewire::actingAs($this->e->dirigente)->test(Revisar::class, ['entrega' => $entrega])->call('pedirRechazo');
    $c->call('rechazar')->assertHasErrors(['motivo'])->assertSet('paso', 'rechazar');
    $c->set('motivo', 'Foto borrosa')->call('rechazar')->assertSet('paso', 'rechazada');

    expect($entrega->fresh())->estado->toBe(EstadoEntrega::Rechazada)->motivo_rechazo->toBe('Foto borrosa');

    // y la persona ve el motivo en su misión
    $this->actingAs($this->yo)->get(route('app.mision', $entrega->mision))->assertSee('Foto borrosa');
});

it('si otro dirigente ya la revisó, avisa y no acredita de más', function () {
    $entrega = Entrega::factory()->create(['mision_id' => misionPara($this->e->unidad, ['puntos' => 40])->id, 'user_id' => $this->yo->id]);
    $c = Livewire::actingAs($this->e->dirigente)->test(Revisar::class, ['entrega' => $entrega]);

    app(AprobarEntrega::class)($this->e->dirigente, $entrega);
    $c->call('aprobar')->assertSet('paso', 'ya');

    expect($this->yo->fresh()->puntos)->toBe(40);
});

it('un dirigente de otra unidad no puede abrir ni revisar la entrega', function () {
    $entrega = Entrega::factory()->create(['mision_id' => misionPara($this->e->unidad)->id, 'user_id' => $this->yo->id]);
    $otro = escenario(1)->dirigente;

    $this->actingAs($otro)->get(route('dirigente.revisar', $entrega))->assertForbidden();
    $this->actingAs($this->yo)->get(route('dirigente.revisar', $entrega))->assertForbidden();
    expect($this->yo->fresh()->puntos)->toBe(0);
});

it('las fotos de la entrega solo se ven una vez procesadas y por quien corresponde', function () {
    $mision = misionPara($this->e->unidad);
    Livewire::actingAs($this->yo)->test(Entregar::class, ['mision' => $mision])->set('nueva', fotoFalsa())->set('texto', 'x')->call('enviar');
    $imagen = Entrega::sole()->imagenes()->sole();

    expect($imagen->procesada_at)->not->toBeNull(); // la cola es sync en los tests
    $this->actingAs($this->e->dirigente)->get(route('dirigente.revisar', Entrega::sole()))->assertSee(route('entregas.imagen', $imagen), false);
    $this->actingAs($this->e->miembros[1])->get(route('entregas.imagen', $imagen))->assertForbidden();
});

it('el ranking del dirigente muestra los nombres de las patrullas, y el del participante sigue anónimo', function () {
    $r = rankingEquipo();

    $this->actingAs($r->dirigente)->get(route('dirigente.ranking'))->assertOk()
        ->assertSee('Pumas')->assertSee('Águilas')->assertSee('G.S. General Aquino')->assertSee('3 integrantes')->assertSee('Patrulla 50 + individuales 90')
        ->assertSee('Personas con más puntos')->assertSee('Carla Duarte')->assertSee('Ana Benítez') // las personas, con nombre, para el dirigente
        ->assertDontSee($r->ana->ci);

    $html = $this->actingAs($r->ana)->get(route('app.ranking'))->assertOk()->getContent();
    expect($html)->not->toContain('Pumas')->not->toContain($r->beto->name)->not->toContain('Personas con más puntos');
});
