<?php

use App\Domain\Misiones\EntregarMision;
use App\Enums\Rol;
use App\Jobs\ProcesarImagenEntrega;
use App\Models\EntregaImagen;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    $this->e = escenario();
    $this->yo = $this->e->miembros[0];
    $this->mision = misionPara($this->e->unidad);
});

function entregarConFoto($test, $foto): EntregaImagen
{
    $entrega = app(EntregarMision::class)($test->yo, $test->mision, 'ok', [$foto]);

    return $entrega->imagenes()->first();
}

it('manda el procesamiento a la cola y deja el original sin servir hasta terminar', function () {
    Queue::fake();

    $imagen = entregarConFoto($this, fotoFalsa());

    Queue::assertPushed(ProcesarImagenEntrega::class, fn ($job) => $job->imagenId === $imagen->id);
    expect($imagen->procesada_at)->toBeNull()
        ->and($imagen->ruta)->toStartWith('tmp/');

    $this->actingAs($this->yo)->get(route('entregas.imagen', $imagen))->assertNotFound();
});

it('re-codifica a JPEG, achica a 1600 px como máximo y borra el original', function () {
    $imagen = entregarConFoto($this, fotoFalsa('grande.png', 3200, 2400))->fresh();
    $disco = Storage::disk('private');

    [$ancho, $alto, $tipo] = getimagesizefromstring($disco->get($imagen->ruta));

    expect($imagen->procesada_at)->not->toBeNull()
        ->and($imagen->ruta)->toStartWith("entregas/{$imagen->entrega_id}/")->toEndWith('.jpg')
        ->and($tipo)->toBe(IMAGETYPE_JPEG)
        ->and(max($ancho, $alto))->toBe(1600)
        ->and($ancho / $alto)->toBeGreaterThan(1.32)->toBeLessThan(1.34)
        ->and($disco->allFiles())->toHaveCount(1); // el original de tmp/ ya no está
});

it('no agranda las imágenes chicas', function () {
    $imagen = entregarConFoto($this, fotoFalsa('chica.jpg', 300, 200))->fresh();

    [$ancho, $alto] = getimagesizefromstring(Storage::disk('private')->get($imagen->ruta));

    expect([$ancho, $alto])->toBe([300, 200]);
});

it('elimina el EXIF, incluida la ubicación GPS', function () {
    $original = file_get_contents(($foto = jpegConExifGps())->getRealPath());
    expect($original)->toContain('GPSLatitude'); // el archivo de entrada sí lo trae

    $imagen = entregarConFoto($this, $foto)->fresh();
    $procesada = Storage::disk('private')->get($imagen->ruta);

    expect($procesada)->not->toContain('GPSLatitude')
        ->and($procesada)->not->toContain('GPSLongitude')
        ->and($procesada)->not->toContain('Exif')
        ->and(exif_read_data('data://image/jpeg;base64,'.base64_encode($procesada), 'GPS', true)['GPS'] ?? [])->toBeEmpty();
});

it('si el procesamiento falla definitivamente, no queda el original en disco ni se sirve', function () {
    Queue::fake();
    $imagen = entregarConFoto($this, fotoFalsa());

    (new ProcesarImagenEntrega($imagen->id))->failed(new RuntimeException('boom'));

    expect(Storage::disk('private')->exists($imagen->ruta))->toBeFalse();
    $this->actingAs($this->yo)->get(route('entregas.imagen', $imagen))->assertNotFound();
});

it('procesar dos veces la misma imagen es inofensivo', function () {
    $imagen = entregarConFoto($this, fotoFalsa())->fresh();
    $ruta = $imagen->ruta;

    (new ProcesarImagenEntrega($imagen->id))->handle();

    expect($imagen->fresh()->ruta)->toBe($ruta);
});

it('sirve la imagen a quien entregó, con cabeceras seguras', function () {
    $imagen = entregarConFoto($this, fotoFalsa())->fresh();

    $this->actingAs($this->yo)->get(route('entregas.imagen', $imagen))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('la sirve al dirigente de la unidad y al admin', function () {
    $imagen = entregarConFoto($this, fotoFalsa())->fresh();
    $admin = User::factory()->rol(Rol::Admin)->create();

    $this->actingAs($this->e->dirigente)->get(route('entregas.imagen', $imagen))->assertOk();
    $this->actingAs($admin)->get(route('entregas.imagen', $imagen))->assertOk();
});

it('en misiones por patrulla la ve cualquier integrante de la patrulla', function () {
    $mision = misionPara($this->e->unidad, [], 'porPatrulla');
    $entrega = app(EntregarMision::class)($this->yo, $mision, 'ok', [fotoFalsa()]);

    $this->actingAs($this->e->miembros[2])->get(route('entregas.imagen', $entrega->imagenes()->first()))->assertOk();
});

it('la niega con 403 a otro participante, a otra patrulla y al dirigente de otra unidad', function () {
    $imagen = entregarConFoto($this, fotoFalsa())->fresh();
    $ruta = route('entregas.imagen', $imagen);
    $companero = $this->e->miembros[1]; // misma patrulla, pero la misión es individual
    $ajeno = escenario();

    $this->actingAs($companero)->get($ruta)->assertForbidden();
    $this->actingAs($ajeno->miembros[0])->get($ruta)->assertForbidden();
    $this->actingAs($ajeno->dirigente)->get($ruta)->assertForbidden();
});

it('pide iniciar sesión a quien no la tiene', function () {
    $imagen = entregarConFoto($this, fotoFalsa())->fresh();

    $this->get(route('entregas.imagen', $imagen))->assertRedirect(route('ingresar'));
});

it('las imágenes no quedan en public/', function () {
    entregarConFoto($this, fotoFalsa());

    expect(config('filesystems.disks.private.root'))->not->toContain('public')
        ->and(config('filesystems.disks.private.visibility'))->toBe('private');
});
