<?php

namespace Database\Seeders;

use App\Domain\Actividades\AbrirSesionAsistencia;
use App\Domain\Actividades\RegistrarAsistencia;
use App\Domain\Actividades\Reservar;
use App\Domain\Actividades\TokenRotativo;
use App\Domain\Contactos\AprobarContacto;
use App\Domain\Contactos\Puntaje;
use App\Domain\Contactos\RechazarContacto;
use App\Domain\Contactos\RegistrarContactoJota;
use App\Domain\Contactos\RegistrarContactoJoti;
use App\Domain\Ledger\Jlis;
use App\Domain\Ledger\Puntos;
use App\Domain\Misiones\AprobarEntrega;
use App\Domain\Misiones\EntregarMision;
use App\Domain\Misiones\RechazarEntrega;
use App\Domain\Penalizaciones\Penalizar;
use App\Domain\Tienda\CrearCobro;
use App\Domain\Tienda\PagarCobro;
use App\Enums\Modalidad;
use App\Enums\TipoActividad;
use App\Jobs\ProcesarCapturaContacto;
use App\Jobs\ProcesarImagenEntrega;
use App\Models\Actividad;
use App\Models\Ajuste;
use App\Models\Mision;
use App\Models\Patrulla;
use App\Models\Reserva;
use App\Models\Turno;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Datos de MUESTRA para recorrer la plataforma en local y ver todas las pantallas con contenido: misiones en sus seis estados,
 * talleres y estaciones en todos sus estados, contactos JOTA y JOTI, ranking con puntos, tienda e historial.
 *
 * Se corre DESPUÉS del seeder base:  php artisan db:seed --class=DatosDeMuestraSeeder
 * Usa las mismas acciones del sistema (no escribe saldos a mano), así los puntos y los LisCoins quedan consistentes.
 * Los horarios son relativos al momento de correrlo: para refrescarlos, volver a empezar con `migrate:fresh --seed`.
 * Nunca en producción. Sin datos reales de nadie.
 */
class DatosDeMuestraSeeder extends Seeder
{
    /** @var array<string, User> */
    private array $u = [];

    private Unidad $tropa;

    private Unidad $caminantes;

    private Unidad $rovers;

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Los datos de muestra no se cargan en producción.');
        }

        if (Ajuste::query()->where('clave', 'datos_de_muestra')->exists()) {
            $this->command?->warn('Los datos de muestra ya están cargados. Para volver a empezar: php artisan migrate:fresh --seed && php artisan db:seed --class=DatosDeMuestraSeeder');

            return;
        }

        $this->cargarBase();

        // Los datos de muestra no ensucian la auditoría de configuración.
        activity()->withoutLogs(function () {
            app(Puntaje::class)->guardar(['jota_local' => 10, 'jota_exterior' => 30, 'joti_local' => 5, 'joti_exterior' => 15], $this->u['admin']);
            $this->misiones();
            $this->talleres();
            $this->estaciones();
            $this->contactos();
            $this->puntosYTienda();
        });

        Ajuste::query()->create(['clave' => 'datos_de_muestra', 'valor' => now()->toDateTimeString()]);
        $this->resumen();
    }

    private function cargarBase(): void
    {
        $porCi = fn (string $ci) => User::query()->where('ci', $ci)->first() ?? throw new RuntimeException("Falta el usuario {$ci}: corré primero `php artisan migrate:fresh --seed`.");

        $this->u = [
            'aguilas1' => $porCi('4000001'), 'aguilas2' => $porCi('4000002'), 'aguilas3' => $porCi('4000003'),
            'pumas1' => $porCi('4000004'), 'pumas2' => $porCi('4000005'), 'pumas3' => $porCi('4000006'),
            'zorros1' => $porCi('4000008'), 'zorros2' => $porCi('4000009'),
            'condores1' => $porCi('4000020'), 'condores2' => $porCi('4000021'), 'yaguares1' => $porCi('4000023'),
            'rover1' => $porCi('4000039'), 'rover2' => $porCi('4000040'), 'rover3' => $porCi('4000041'),
            'dirTropa' => $porCi('4000045'), 'dirCaminantes' => $porCi('4000046'), 'dirRovers' => $porCi('4000047'),
            'tienda' => $porCi('4000048'), 'admin' => $porCi('4000049'),
        ];

        $this->tropa = Unidad::query()->where('nombre', 'Tropa')->firstOrFail();
        $this->caminantes = Unidad::query()->where('nombre', 'Caminantes')->firstOrFail();
        $this->rovers = Unidad::query()->where('nombre', 'Rovers')->firstOrFail();
    }

    // ── Misiones: los seis estados para Águilas 1 ───────────────────────────

    private function misiones(): void
    {
        $crear = function (string $titulo, string $lugar, ?string $descripcion, Modalidad $modalidad, int $puntos, array $unidades, array $fechas = []) {
            $m = Mision::query()->create([
                'titulo' => $titulo, 'lugar' => $lugar, 'descripcion' => $descripcion ?? 'Hacé lo que pide la misión y contanos cómo te fue.',
                'modalidad' => $modalidad, 'puntos' => $puntos,
                'lanza_at' => $fechas['lanza'] ?? now()->subHours(3), 'vence_at' => $fechas['vence'] ?? now()->addHours(8), 'cerrada_at' => $fechas['cierra'] ?? null,
            ]);
            $m->unidades()->attach(array_map(fn ($x) => $x->id, $unidades));

            return $m;
        };
        $unidades = [$this->tropa, $this->caminantes];

        // RECHAZADA: la entregó y el dirigente la rechazó; sigue abierta, se puede volver a entregar.
        $rechazada = $crear('Foto de la bandera', 'Mástil de la entrada', 'Sacale una foto a la bandera de tu patrulla y contanos qué significa.', Modalidad::Individual, 20, $unidades);
        $entrega = app(EntregarMision::class)($this->u['aguilas1'], $rechazada, 'Esta es la bandera de mi patrulla.', [$this->imagen('Bandera')]);
        $this->procesarFotos($entrega);
        app(RechazarEntrega::class)($this->u['dirTropa'], $entrega, 'La foto salió borrosa, probá de nuevo con más luz.');

        // NUEVAS: todavía no entregó nada.
        $crear('Dibujá el mapa del campamento', 'Fogón central', 'Dibujá un mapa simple del campamento y marcá tu carpa.', Modalidad::Individual, 15, $unidades);
        $crear('Mensaje en clave 2', 'Carpa de dirigentes', 'Descifrá el mensaje secreto y mandá la respuesta.', Modalidad::Individual, 25, $unidades, ['vence' => now()->addHours(5)]);
        $crear('Canción de la patrulla', 'Cancha de fútbol', 'Inventen entre todos una canción corta para su patrulla.', Modalidad::Patrulla, 40, [$this->tropa]);

        // EN REVISIÓN: entregada, espera al dirigente (queda para mostrar la cola).
        $revision = $crear('Cuento de fogón', 'Fogón central', 'Escribí un cuento corto de fogón, de cinco renglones como máximo.', Modalidad::Individual, 25, $unidades);
        app(EntregarMision::class)($this->u['aguilas1'], $revision, 'Había una vez una patrulla que perdió su farol y lo encontró en el bosque…');
        $otra = app(EntregarMision::class)($this->u['pumas1'], $revision, 'Mi cuento trata de un zorro que aprendió a hacer nudos.');
        $this->procesarFotos($otra);

        // APROBADA: la patrulla Águilas ya la hizo (suma puntos a la patrulla y LisCoins a cada integrante).
        $aprobada = $crear('Nudos en equipo 2', 'Campo de patrullas', 'Armen entre toda la patrulla un nudo de ocho y mandá la foto del equipo.', Modalidad::Patrulla, 50, [$this->tropa]);
        $e = app(EntregarMision::class)($this->u['aguilas2'], $aprobada, 'Lo armamos entre todos.', [$this->imagen('Nudo de ocho')]);
        $this->procesarFotos($e);
        app(AprobarEntrega::class)($this->u['dirTropa'], $e);

        // Una individual aprobada también.
        $indAprobada = $crear('Presentate a tu patrulla', 'Entrada principal', 'Contanos quién sos y qué es lo que más te gusta del campamento.', Modalidad::Individual, 10, $unidades);
        app(AprobarEntrega::class)($this->u['dirTropa'], app(EntregarMision::class)($this->u['aguilas1'], $indAprobada, 'Soy Águilas 1 y me gustan las fogatas.'));

        // VENCIDA y CERRADA: ya no se pueden entregar.
        $crear('Desafío del amanecer', 'Plaza central', null, Modalidad::Individual, 20, $unidades, ['lanza' => now()->subHours(6), 'vence' => now()->subHour()]);
        $crear('Búsqueda del tesoro', 'Bosque', null, Modalidad::Individual, 30, $unidades, ['lanza' => now()->subHours(5), 'vence' => null, 'cierra' => now()->subMinutes(30)]);

        // Para los Rovers.
        $crear('Bitácora del servicio', 'Fogón Rover', 'Escribí una reflexión corta sobre tu servicio en este evento.', Modalidad::Individual, 40, [$this->rovers]);
    }

    // ── Talleres: en curso, con lugar, apuntado y lleno ─────────────────────

    private function talleres(): void
    {
        $taller = function (string $titulo, string $lugar, Modalidad $modalidad, array $unidades, int $puntos = 15) {
            $a = Actividad::query()->create(['tipo' => TipoActividad::Taller, 'titulo' => $titulo, 'lugar' => $lugar, 'modalidad' => $modalidad, 'puntos' => $puntos]);
            $a->unidades()->attach(array_map(fn ($x) => $x->id, $unidades));

            return $a;
        };
        $turno = fn (Actividad $a, string $desde, string $hasta, ?int $cupo) => Turno::query()->create(['actividad_id' => $a->id, 'inicio' => now()->modify($desde), 'fin' => now()->modify($hasta), 'cupo' => $cupo]);
        $anotar = function (Turno $t, array $personas) {
            foreach ($personas as $p) {
                app(Reservar::class)($p, $t);
            }
        };
        $otros = [$this->u['pumas1'], $this->u['pumas2'], $this->u['pumas3'], $this->u['zorros1'], $this->u['zorros2'], $this->u['aguilas3']];

        // EN CURSO con otros dos turnos (con lugar y lleno): se ve cómo se ordenan los turnos de un mismo taller.
        $ia = $taller('IA para tus ideas', 'Auditorio', Modalidad::Individual, [$this->tropa]);
        $anotar($turno($ia, '-20 minutes', '+25 minutes', 30), array_slice($otros, 0, 4));
        $turno($ia, '+90 minutes', '+135 minutes', 25);
        $lleno = $turno($ia, '+3 hours', '+3 hours 45 minutes', 2);
        $anotar($lleno, [$this->u['aguilas3'], $this->u['zorros2']]); // gente que NO está ya en otro turno de este taller

        // CON LUGAR por equipo (cupo en patrullas).
        $cocina = $taller('Cocina scout', 'Cocina de campo', Modalidad::Patrulla, [$this->tropa], 30);
        $turno($cocina, '+4 hours', '+5 hours', 10);

        // APUNTADO: Águilas 1 ya está anotado.
        $scratch = $taller('Programación con Scratch', 'Salón 2', Modalidad::Individual, [$this->tropa]);
        $anotar($turno($scratch, '+2 hours', '+2 hours 45 minutes', 15), [$this->u['aguilas1'], $this->u['pumas2']]);
        $turno($scratch, '+5 hours', '+5 hours 45 minutes', 15); // otro turno: queda en gris, porque ya está anotado en este taller

        // LLENO.
        $foto = $taller('Fotografía con el celular', 'Galpón', Modalidad::Individual, [$this->tropa]);
        $anotar($turno($foto, '+3 hours', '+3 hours 45 minutes', 1), [$this->u['pumas3']]);

        // Con asistencia por QR para mostrar «asistencia registrada» en el historial.
        $primeros = Actividad::query()->where('titulo', 'Taller de primeros auxilios')->first();
        if ($primeros) {
            $sesion = app(AbrirSesionAsistencia::class)($this->u['dirTropa'], $primeros);
            app(RegistrarAsistencia::class)($this->u['aguilas2'], $sesion, app(TokenRotativo::class)->generar($sesion));
        }

        // Caminantes y Rovers.
        $cyber = $taller('Ciberseguridad para scouts', 'Auditorio', Modalidad::Individual, [$this->caminantes]);
        $anotar($turno($cyber, '-10 minutes', '+35 minutes', 40), [$this->u['condores2']]);
        $turno($cyber, '+2 hours', '+2 hours 45 minutes', 40);
        $liderazgo = $taller('Liderazgo y servicio', 'Fogón Rover', Modalidad::Individual, [$this->rovers], 25);
        $turno($liderazgo, '+1 hour', '+2 hours', 12);
    }

    // ── Estaciones: reservada, disponible, en curso del equipo; y las que NO se ven ──

    private function estaciones(): void
    {
        $estacion = function (string $titulo, string $lugar, array $unidades) {
            $a = Actividad::query()->create(['tipo' => TipoActividad::EstacionJota, 'titulo' => $titulo, 'lugar' => $lugar, 'modalidad' => Modalidad::Patrulla, 'puntos' => 20]);
            $a->unidades()->attach(array_map(fn ($x) => $x->id, $unidades));

            return $a;
        };
        $turno = fn (Actividad $a, string $desde, string $hasta) => Turno::query()->create(['actividad_id' => $a->id, 'inicio' => now()->modify($desde), 'fin' => now()->modify($hasta), 'cupo' => 1]);

        // DISPONIBLES para Águilas, que todavía no reservó ninguna; el segundo horario lo tiene Zorros (ocupada: Águilas no la ve).
        $radio = $estacion('Estación JOTA: radio y morse', 'Carpa de radio', [$this->tropa, $this->caminantes]);
        $turno($radio, '+30 minutes', '+75 minutes');
        $ocupada = $turno($radio, '+2 hours', '+2 hours 45 minutes');
        Reserva::query()->create(['turno_id' => $ocupada->id, 'patrulla_id' => $this->u['zorros1']->patrulla_id]);

        // EN CURSO de Pumas: Pumas la reservó antes y ya empezó (Pumas la ve «en curso»; Águilas ve solo el horario libre de después).
        $brujula = $estacion('Estación JOTA: brújula y mapa', 'Bosque', [$this->tropa]);
        $enCurso = $turno($brujula, '-15 minutes', '+30 minutes');
        Reserva::query()->create(['turno_id' => $enCurso->id, 'patrulla_id' => $this->u['pumas1']->patrulla_id]);
        $turno($brujula, '+1 hour', '+1 hour 45 minutes');

        // YA EMPEZÓ sin ser de nadie: no se muestra a ningún equipo.
        $humo = $estacion('Estación JOTA: señales de humo', 'Claro del bosque', [$this->tropa]);
        $turno($humo, '-10 minutes', '+35 minutes');

        // RESERVADA por el equipo Cóndores (Caminantes): con eso, ven solo esa y no las demás.
        $juego = $estacion('Estación JOTA: gran juego', 'Cancha de fútbol', [$this->caminantes]);
        Reserva::query()->create(['turno_id' => $turno($juego, '+45 minutes', '+90 minutes')->id, 'patrulla_id' => $this->u['condores1']->patrulla_id]);
        $turno($juego, '+3 hours', '+3 hours 45 minutes');

        // Rovers: la unidad entera es el equipo.
        $servicio = $estacion('Estación JOTA: servicio comunitario', 'Plaza', [$this->rovers]);
        $turno($servicio, '+1 hour', '+2 hours');
    }

    // ── Contactos JOTA y JOTI ───────────────────────────────────────────────

    private function contactos(): void
    {
        $joti = fn (User $user, array $d) => tap(app(RegistrarContactoJoti::class)($user, $d, $this->imagen('Conversación')), function ($c) {
            (new ProcesarCapturaContacto($c->id))->handle(); // sin esperar a la cola
        });
        $jota = fn (User $user, array $d) => app(RegistrarContactoJota::class)($user, $d + ['rst_tx' => '5-9', 'rst_rx' => '5-9']);

        // JOTI de Águilas 1: uno por cada estado.
        $aprobadoJoti = $joti($this->u['aguilas1'], ['grupo_scout' => 'Grupo Scout Itauguá', 'ciudad' => 'Encarnación', 'unidad_scout' => 'Tropa', 'pais' => 'Paraguay']);
        app(AprobarContacto::class)($this->u['dirTropa'], $aprobadoJoti);
        $joti($this->u['aguilas1'], ['grupo_scout' => 'Grupo Scout Lima 1', 'ciudad' => 'Lima', 'unidad_scout' => 'Caminantes', 'pais' => 'Perú']);
        $rechazadoJoti = $joti($this->u['aguilas1'], ['grupo_scout' => 'Grupo Scout Córdoba', 'ciudad' => 'Córdoba', 'unidad_scout' => 'Tropa', 'pais' => 'Argentina']);
        app(RechazarContacto::class)($this->u['dirTropa'], $rechazadoJoti, 'La captura no se ve: sacala de nuevo con la conversación completa.');
        $joti($this->u['pumas1'], ['grupo_scout' => 'Grupo Scout Quito 2', 'ciudad' => 'Quito', 'unidad_scout' => 'Tropa', 'pais' => 'Ecuador']);
        $joti($this->u['condores1'], ['grupo_scout' => 'Grupo Scout Asunción 3', 'ciudad' => 'Asunción', 'unidad_scout' => 'Caminantes', 'pais' => 'Paraguay']);

        // JOTA de la patrulla Águilas (libro de guardia): local aprobado, exterior en revisión y uno rechazado.
        $aprobadoJota = $jota($this->u['aguilas1'], ['contactado_at' => now()->subHours(2)->format('Y-m-d H:i'), 'estacion' => 'ZP5ZDM', 'frecuencia' => '7.125', 'emision' => 'LSB', 'obs' => 'Christian', 'qsl_e' => true]);
        app(AprobarContacto::class)($this->u['dirTropa'], $aprobadoJota);
        $jota($this->u['aguilas2'], ['contactado_at' => now()->subHour()->format('Y-m-d H:i'), 'estacion' => 'LU1ABC', 'frecuencia' => '14.025', 'emision' => 'USB', 'rst_rx' => '5-4', 'qsl_r' => true]);
        $rechazadoJota = $jota($this->u['aguilas3'], ['contactado_at' => now()->subMinutes(40)->format('Y-m-d H:i'), 'estacion' => 'ZP6ASP', 'frecuencia' => '7.100', 'emision' => 'LSB']);
        app(RechazarContacto::class)($this->u['dirTropa'], $rechazadoJota, 'La hora no coincide con el libro de guardia.');
        $jota($this->u['pumas1'], ['contactado_at' => now()->subMinutes(25)->format('Y-m-d H:i'), 'estacion' => 'CX2ABC', 'frecuencia' => '21.200', 'emision' => 'FT8']);
        $jota($this->u['rover1'], ['contactado_at' => now()->subMinutes(15)->format('Y-m-d H:i'), 'estacion' => 'ZP9XYZ', 'frecuencia' => '3.690', 'emision' => 'LSB']);
    }

    // ── Puntos para el ranking, LisCoins, una penalización y un pago en la tienda ──

    private function puntosYTienda(): void
    {
        $puntos = app(Puntos::class);
        $jlis = app(Jlis::class);
        $admin = $this->u['admin'];

        foreach (['Águilas' => 40, 'Pumas' => 150, 'Zorros' => 70, 'Jaguares' => 95, 'Cóndores' => 120, 'Yaguaretés' => 60, 'Tucanes' => 85, 'Carayás' => 30] as $nombre => $cantidad) {
            $patrulla = Patrulla::query()->where('nombre', $nombre)->first();
            $patrulla && $puntos->patrulla($patrulla, $cantidad, null, 'Muestra: puntos de la patrulla', $admin);
        }

        $individuales = ['aguilas2' => 35, 'aguilas3' => 12, 'pumas2' => 28, 'pumas3' => 41, 'zorros1' => 18, 'condores1' => 33, 'condores2' => 9, 'yaguares1' => 22, 'rover1' => 120, 'rover2' => 95, 'rover3' => 60];
        foreach ($individuales as $clave => $cantidad) {
            $puntos->individual($this->u[$clave], $cantidad, null, 'Muestra: puntos individuales', $admin);
            $jlis->acreditar($this->u[$clave], $cantidad, null, 'Muestra: LisCoins', $admin);
        }

        // Una penalización, y un pago en la tienda para el historial de Águilas 1 y del puesto.
        app(Penalizar::class)->persona($this->u['dirTropa'], $this->u['zorros1'], 5, 'Llegó tarde a la formación.');
        $jlis->acreditar($this->u['aguilas1'], 40, null, 'Muestra: LisCoins para la tienda', $admin);
        app(PagarCobro::class)($this->u['aguilas1'], app(CrearCobro::class)($this->u['tienda'], 12)->token);
    }

    // ── Utilidades ──────────────────────────────────────────────────────────

    /** Una imagen PNG simple con un texto, lista para subir como foto o captura de muestra. */
    private function imagen(string $texto): UploadedFile
    {
        $lienzo = imagecreatetruecolor(640, 480);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 232, 224, 250));
        imagestring($lienzo, 5, 24, 24, 'Muestra: '.$texto, imagecolorallocate($lienzo, 70, 40, 140));
        $ruta = tempnam(sys_get_temp_dir(), 'muestra').'.png';
        imagepng($lienzo, $ruta);

        return new UploadedFile($ruta, 'muestra.png', 'image/png', null, true);
    }

    private function procesarFotos($entrega): void
    {
        foreach ($entrega->imagenes as $imagen) {
            (new ProcesarImagenEntrega($imagen->getKey()))->handle();
        }
    }

    private function resumen(): void
    {
        $this->command?->info('Datos de muestra cargados. Para recorrerlos:');
        $this->command?->line('  · Águilas 1 (CI 4000001): misiones en los 6 estados, talleres, estaciones disponibles y contactos JOTA/JOTI.');
        $this->command?->line('  · Pumas 1 (CI 4000004): su equipo tiene una estación reservada en curso.');
        $this->command?->line('  · Cóndores 1 (CI 4000020, Caminantes): su equipo tiene una estación reservada.');
        $this->command?->line('  · Dirigente Tropa (CI 4000045): cola de entregas y de contactos por revisar, y ranking con nombres.');
        $this->command?->line('  · Tienda (CI 4000048) y Administrador (CI 4000049, hace falta crearle contraseña con jota:crear-admin).');
    }
}
