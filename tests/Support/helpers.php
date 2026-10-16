<?php

use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Puntos;
use App\Enums\ModoRanking;
use App\Enums\Rol;
use App\Models\Actividad;
use App\Models\Grupo;
use App\Models\Mision;
use App\Models\Patrulla;
use App\Models\Turno;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/** Una unidad con una patrulla de $integrantes personas y un dirigente. */
function escenario(int $integrantes = 3): object
{
    $unidad = Unidad::factory()->create();
    $patrulla = Patrulla::factory()->create(['unidad_id' => $unidad->id]);
    $miembros = User::factory()->count($integrantes)->create(['unidad_id' => $unidad->id, 'patrulla_id' => $patrulla->id, 'grupo_id' => $patrulla->grupo_id]);
    $dirigente = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => $unidad->id]);

    return (object) compact('unidad', 'patrulla', 'miembros', 'dirigente');
}

function misionPara(Unidad $unidad, array $atributos = [], ?string $estado = null): Mision
{
    $fabrica = Mision::factory();
    $fabrica = $estado ? $fabrica->{$estado}() : $fabrica;
    $mision = $fabrica->create($atributos);
    $mision->unidades()->attach($unidad);

    return $mision;
}

function fotoFalsa(string $nombre = 'foto.jpg', int $ancho = 800, int $alto = 600): UploadedFile
{
    return UploadedFile::fake()->image($nombre, $ancho, $alto);
}

/** JPEG real con un segmento EXIF (APP1) que incluye una marca de ubicación GPS falsa. */
function jpegConExifGps(int $ancho = 400, int $alto = 300): UploadedFile
{
    ob_start();
    $lienzo = imagecreatetruecolor($ancho, $alto);
    imagejpeg($lienzo);
    $jpeg = ob_get_clean();

    $exif = "Exif\0\0GPSLatitude=-25.2637;GPSLongitude=-57.5759";
    $app1 = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;
    $conExif = substr($jpeg, 0, 2).$app1.substr($jpeg, 2);

    $ruta = tempnam(sys_get_temp_dir(), 'exif');
    file_put_contents($ruta, $conExif);

    return new UploadedFile($ruta, 'gps.jpg', 'image/jpeg', null, true);
}

function actividadPara(Unidad $unidad, array $atributos = [], bool $porEquipo = false): Actividad
{
    $fabrica = Actividad::factory();
    $actividad = ($porEquipo ? $fabrica->porEquipo() : $fabrica)->create($atributos);
    $actividad->unidades()->attach($unidad);

    return $actividad;
}

/** Turno de $actividad entre dos momentos relativos a ahora, p. ej. turnoDe($a, '+1 hour', '+2 hours', cupo: 3). */
function turnoDe(Actividad $actividad, string $desde = '+1 hour', string $hasta = '+2 hours', ?int $cupo = null): Turno
{
    return Turno::factory()->create([
        'actividad_id' => $actividad->id,
        'inicio' => now()->modify($desde),
        'fin' => now()->modify($hasta),
        'cupo' => $cupo,
    ]);
}

function usuarioTienda(): User
{
    return User::factory()->rol(Rol::Tienda)->create();
}

/** Beneficiario con $jlis de saldo, cargado por el ledger (con su movimiento). */
function conJlis(int $jlis, ?User $user = null): User
{
    $user ??= User::factory()->create();

    if ($jlis > 0) {
        app(Jlis::class)->acreditar($user, $jlis, motivo: 'Saldo de prueba');
    }

    return $user->fresh();
}

function darPuntos(User $user, int $puntos): User
{
    app(Puntos::class)->individual($user, $puntos, motivo: 'Puntos de prueba');

    return $user->fresh();
}

function darPuntosPatrulla(Patrulla $patrulla, int $puntos): void
{
    app(Puntos::class)->patrulla($patrulla, $puntos, motivo: 'Puntos de prueba');
}

/**
 * Unidad de equipo con tres patrullas de nombres reconocibles:
 *  Águilas: 100 de patrulla + 10 + 20 individuales = 130
 *  Pumas:    50 de patrulla + 40 + 40 + 10         = 140
 *  Zorros:   sin integrantes ni puntos             = 0
 */
function rankingEquipo(string $nombreUnidad = 'Tropa'): object
{
    $unidad = Unidad::factory()->create(['nombre' => $nombreUnidad]);
    $grupoA = Grupo::firstOrCreate(['nombre' => 'G.S. General Genes']);
    $grupoB = Grupo::firstOrCreate(['nombre' => 'G.S. General Aquino']);

    $patrulla = fn (string $nombre, $grupo) => Patrulla::factory()->create(['nombre' => $nombre, 'unidad_id' => $unidad->id, 'grupo_id' => $grupo->id]);
    $miembro = fn ($patrulla, string $nombre, int $puntos) => darPuntos(
        User::factory()->create(['name' => $nombre, 'unidad_id' => $unidad->id, 'patrulla_id' => $patrulla->id, 'grupo_id' => $patrulla->grupo_id]),
        $puntos,
    );

    $aguilas = $patrulla('Águilas', $grupoA);
    $pumas = $patrulla('Pumas', $grupoB);
    $zorros = $patrulla('Zorros', $grupoA);
    darPuntosPatrulla($aguilas, 100);
    darPuntosPatrulla($pumas, 50);

    $ana = $miembro($aguilas, 'Ana Benítez', 10);
    $beto = $miembro($aguilas, 'Beto Cardozo', 20);
    $carla = $miembro($pumas, 'Carla Duarte', 40);
    $diego = $miembro($pumas, 'Diego Escobar', 40);
    $elena = $miembro($pumas, 'Elena Franco', 10);

    $dirigente = User::factory()->rol(Rol::Dirigente)->create(['unidad_id' => $unidad->id, 'name' => 'Dirigente Gómez']);

    return (object) compact('unidad', 'aguilas', 'pumas', 'zorros', 'ana', 'beto', 'carla', 'diego', 'elena', 'dirigente');
}

/** Estructura mínima para importar: 2 grupos, Tropa (equipo) y Rovers (individual). */
function estructuraImportacion(): object
{
    $genes = Grupo::create(['nombre' => 'G.S. General Genes']);
    $aquino = Grupo::create(['nombre' => 'G.S. General Aquino']);
    $tropa = Unidad::create(['nombre' => 'Tropa', 'modo_ranking' => ModoRanking::Equipo]);
    $rovers = Unidad::create(['nombre' => 'Rovers', 'modo_ranking' => ModoRanking::Individual]);

    return (object) compact('genes', 'aquino', 'tropa', 'rovers');
}

function adminDePrueba(): User
{
    return User::factory()->rol(Rol::Admin)->create();
}
