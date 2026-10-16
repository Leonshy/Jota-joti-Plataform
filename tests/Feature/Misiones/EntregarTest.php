<?php

use App\Domain\Misiones\AprobarEntrega;
use App\Domain\Misiones\EntregaBloqueada;
use App\Domain\Misiones\EntregaInvalida;
use App\Domain\Misiones\EntregarMision;
use App\Domain\Misiones\MisionCerrada;
use App\Domain\Misiones\RechazarEntrega;
use App\Domain\Misiones\SinPatrulla;
use App\Enums\EstadoEntrega;
use App\Models\Entrega;
use App\Models\Patrulla;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    $this->e = escenario();
    $this->yo = $this->e->miembros[0];
    $this->entregar = app(EntregarMision::class);
});

it('entrega una misión individual solo con texto', function () {
    $mision = misionPara($this->e->unidad);

    $entrega = ($this->entregar)($this->yo, $mision, '  Mi respuesta  ');

    expect($entrega->estado)->toBe(EstadoEntrega::Pendiente)
        ->and($entrega->texto)->toBe('Mi respuesta')
        ->and($entrega->user_id)->toBe($this->yo->id)
        ->and($entrega->patrulla_id)->toBeNull();
});

it('entrega solo con fotos', function () {
    $mision = misionPara($this->e->unidad);

    $entrega = ($this->entregar)($this->yo, $mision, null, [fotoFalsa('a.jpg'), fotoFalsa('b.png')]);

    expect($entrega->texto)->toBeNull()->and($entrega->imagenes)->toHaveCount(2);
});

it('exige texto o al menos una foto', function () {
    ($this->entregar)($this->yo, misionPara($this->e->unidad), '   ');
})->throws(EntregaInvalida::class);

it('acepta hasta 4 fotos y rechaza la quinta', function () {
    $mision = misionPara($this->e->unidad);
    $fotos = fn (int $n) => array_map(fn ($i) => fotoFalsa("f{$i}.jpg"), range(1, $n));

    expect(($this->entregar)($this->yo, $mision, 'ok', $fotos(4))->imagenes)->toHaveCount(4);

    $otra = $this->e->miembros[1];
    expect(fn () => ($this->entregar)($otra, $mision, 'ok', $fotos(5)))->toThrow(EntregaInvalida::class)
        ->and(Entrega::where('user_id', $otra->id)->count())->toBe(0);
});

it('valida el contenido real: un archivo que no es imagen no pasa aunque se llame .jpg', function () {
    $falso = UploadedFile::fake()->createWithContent('truco.jpg', '<?php echo "hola"; ?>');

    ($this->entregar)($this->yo, misionPara($this->e->unidad), 'ok', [$falso]);
})->throws(EntregaInvalida::class, 'JPG, PNG o WebP');

it('rechaza imágenes de más de 8 MB y formatos no permitidos', function () {
    $mision = misionPara($this->e->unidad);
    $pesada = UploadedFile::fake()->image('grande.jpg')->size(8 * 1024 + 1);
    $gif = UploadedFile::fake()->image('animada.gif');

    expect(fn () => ($this->entregar)($this->yo, $mision, 'ok', [$pesada]))->toThrow(EntregaInvalida::class)
        ->and(fn () => ($this->entregar)($this->yo, $mision, 'ok', [$gif]))->toThrow(EntregaInvalida::class);
});

it('limita el texto a 2000 caracteres', function () {
    ($this->entregar)($this->yo, misionPara($this->e->unidad), str_repeat('a', 2001));
})->throws(EntregaInvalida::class);

it('no deja entregar una misión que todavía no se lanzó', function () {
    ($this->entregar)($this->yo, misionPara($this->e->unidad, [], 'futura'), 'ok');
})->throws(AuthorizationException::class);

it('no deja entregar una misión de otra unidad', function () {
    $ajena = misionPara(escenario()->unidad);

    ($this->entregar)($this->yo, $ajena, 'ok');
})->throws(AuthorizationException::class);

it('el dirigente solo consulta: no entrega', function () {
    ($this->entregar)($this->e->dirigente, misionPara($this->e->unidad), 'ok');
})->throws(AuthorizationException::class);

it('no deja entregar una misión vencida ni cerrada', function (string $estado, string $razon) {
    $mision = misionPara($this->e->unidad, [], $estado);

    try {
        ($this->entregar)($this->yo, $mision, 'ok');
        $this->fail('Debió lanzar MisionCerrada');
    } catch (MisionCerrada $e) {
        expect($e->razon)->toBe($razon);
    }
})->with([['vencida', 'vencida'], ['cerrada', 'cerrada']]);

it('una misión que vence entre abrir la pantalla y entregar se rechaza', function () {
    $mision = misionPara($this->e->unidad, ['vence_at' => now()->addMinutes(5)]);
    $this->travel(6)->minutes();

    ($this->entregar)($this->yo, $mision, 'tarde');
})->throws(MisionCerrada::class);

it('una entrega pendiente bloquea una nueva de la misma persona', function () {
    $mision = misionPara($this->e->unidad);
    ($this->entregar)($this->yo, $mision, 'primera');

    try {
        ($this->entregar)($this->yo, $mision, 'segunda');
        $this->fail('Debió bloquearse');
    } catch (EntregaBloqueada $e) {
        expect($e->estado)->toBe(EstadoEntrega::Pendiente);
    }

    expect(Entrega::count())->toBe(1);
});

it('en una misión individual la pendiente de otra persona no bloquea', function () {
    $mision = misionPara($this->e->unidad);
    ($this->entregar)($this->e->miembros[0], $mision, 'a');
    ($this->entregar)($this->e->miembros[1], $mision, 'b');

    expect(Entrega::count())->toBe(2);
});

it('una misión aprobada no se puede volver a entregar', function () {
    $mision = misionPara($this->e->unidad);
    $entrega = ($this->entregar)($this->yo, $mision, 'primera');
    app(AprobarEntrega::class)($this->e->dirigente, $entrega);

    try {
        ($this->entregar)($this->yo, $mision, 'otra vez');
        $this->fail('Debió bloquearse');
    } catch (EntregaBloqueada $e) {
        expect($e->estado)->toBe(EstadoEntrega::Aprobada);
    }
});

it('tras un rechazo se puede volver a entregar mientras la misión siga abierta', function () {
    $mision = misionPara($this->e->unidad);
    $primera = ($this->entregar)($this->yo, $mision, 'primera');
    app(RechazarEntrega::class)($this->e->dirigente, $primera, 'Falta la foto');

    $segunda = ($this->entregar)($this->yo, $mision, 'segunda');

    expect($segunda->id)->not->toBe($primera->id)
        ->and($primera->fresh()->estado)->toBe(EstadoEntrega::Rechazada)
        ->and(Entrega::where('mision_id', $mision->id)->count())->toBe(2);
});

it('tras un rechazo, si la misión venció, ya no se puede entregar', function () {
    $mision = misionPara($this->e->unidad, ['vence_at' => now()->addMinutes(10)]);
    $primera = ($this->entregar)($this->yo, $mision, 'primera');
    app(RechazarEntrega::class)($this->e->dirigente, $primera, 'Falta la foto');
    $this->travel(11)->minutes();

    ($this->entregar)($this->yo, $mision, 'segunda');
})->throws(MisionCerrada::class);

it('en misiones por patrulla entrega cualquier integrante en nombre de la patrulla', function () {
    $mision = misionPara($this->e->unidad, [], 'porPatrulla');

    $entrega = ($this->entregar)($this->e->miembros[1], $mision, 'Hecho por todos');

    expect($entrega->patrulla_id)->toBe($this->e->patrulla->id)
        ->and($entrega->user_id)->toBe($this->e->miembros[1]->id);
});

it('en misiones por patrulla la pendiente de un compañero bloquea a toda la patrulla', function () {
    $mision = misionPara($this->e->unidad, [], 'porPatrulla');
    ($this->entregar)($this->e->miembros[0], $mision, 'del compañero');

    ($this->entregar)($this->e->miembros[1], $mision, 'mía');
})->throws(EntregaBloqueada::class);

it('en misiones por patrulla otra patrulla no se ve bloqueada', function () {
    $mision = misionPara($this->e->unidad, [], 'porPatrulla');
    $otra = Patrulla::factory()->create(['unidad_id' => $this->e->unidad->id]);
    $deOtra = User::factory()->create(['unidad_id' => $this->e->unidad->id, 'patrulla_id' => $otra->id]);

    ($this->entregar)($this->e->miembros[0], $mision, 'uno');
    ($this->entregar)($deOtra, $mision, 'dos');

    expect(Entrega::count())->toBe(2);
});

it('una misión por patrulla aprobada bloquea a toda la patrulla', function () {
    $mision = misionPara($this->e->unidad, [], 'porPatrulla');
    $entrega = ($this->entregar)($this->e->miembros[0], $mision, 'uno');
    app(AprobarEntrega::class)($this->e->dirigente, $entrega);

    ($this->entregar)($this->e->miembros[2], $mision, 'otro intento');
})->throws(EntregaBloqueada::class);

it('una misión por patrulla exige pertenecer a una patrulla', function () {
    $sinPatrulla = User::factory()->create(['unidad_id' => $this->e->unidad->id]);

    ($this->entregar)($sinPatrulla, misionPara($this->e->unidad, [], 'porPatrulla'), 'ok');
})->throws(SinPatrulla::class);

it('si falla la entrega no quedan archivos huérfanos en el disco', function () {
    $mision = misionPara($this->e->unidad);
    ($this->entregar)($this->yo, $mision, 'primera');
    $antes = count(Storage::disk('private')->allFiles());

    try {
        ($this->entregar)($this->yo, $mision, 'segunda', [fotoFalsa()]);
    } catch (EntregaBloqueada) {
    }

    expect(count(Storage::disk('private')->allFiles()))->toBe($antes);
});
