<?php

namespace Database\Seeders;

use App\Enums\Modalidad;
use App\Enums\ModoRanking;
use App\Enums\Rol;
use App\Enums\TipoActividad;
use App\Models\Actividad;
use App\Models\Grupo;
use App\Models\Mision;
use App\Models\Patrulla;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Datos de prueba (CLAUDE.md §6). Las credenciales viven solo acá y en .env.example (SEED_PIN).
 * Misiones y actividades de ejemplo se agregan en las fases 4 y 5, cuando existan sus modelos.
 */
class DatabaseSeeder extends Seeder
{
    private string $pinHash;

    private int $ci = 4_000_000;

    public function run(): void
    {
        // Los datos de prueba no ensucian la auditoría de configuración.
        activity()->withoutLogs(fn () => $this->sembrar());
    }

    private function sembrar(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('El seeder de prueba no se corre en producción.');
        }

        $this->pinHash = Hash::make(config('jota.seed_pin'));

        $grupos = collect(['G.S. General Genes', 'G.S. General Aquino'])
            ->map(fn ($nombre) => Grupo::create(['nombre' => $nombre]));

        $tropa = Unidad::create(['nombre' => 'Tropa', 'modo_ranking' => ModoRanking::Equipo]);
        $caminantes = Unidad::create(['nombre' => 'Caminantes', 'modo_ranking' => ModoRanking::Equipo]);
        $rovers = Unidad::create(['nombre' => 'Rovers', 'modo_ranking' => ModoRanking::Individual]);

        // 4 patrullas por unidad de equipo, con integrantes dispares (3 a 7) a propósito.
        $tamanos = [3, 4, 5, 7];
        foreach ([[$tropa, ['Águilas', 'Pumas', 'Zorros', 'Jaguares']], [$caminantes, ['Cóndores', 'Yaguaretés', 'Tucanes', 'Carayás']]] as [$unidad, $nombres]) {
            foreach ($nombres as $i => $nombre) {
                $grupo = $grupos[$i % 2];
                $patrulla = Patrulla::create(['nombre' => $nombre, 'grupo_id' => $grupo->id, 'unidad_id' => $unidad->id]);

                for ($n = 1; $n <= $tamanos[$i]; $n++) {
                    $this->usuario(Rol::Beneficiario, "{$nombre} {$n}", $grupo, $unidad, $patrulla);
                }
            }
        }

        for ($n = 1; $n <= 6; $n++) {
            $this->usuario(Rol::Beneficiario, "Rover {$n}", $grupos[$n % 2], $rovers);
        }

        foreach ([$tropa, $caminantes, $rovers] as $unidad) {
            $this->usuario(Rol::Dirigente, "Dirigente {$unidad->nombre}", $grupos[0], $unidad);
        }

        $this->misiones($tropa, $caminantes, $rovers);
        $this->actividades($tropa, $caminantes, $rovers);

        $this->usuario(Rol::Tienda, 'Tienda JOTA', $grupos[0]);
        $this->usuario(Rol::Admin, 'Administrador', $grupos[0]);
    }

    /** Lanzamientos en el pasado y en el futuro, una vencida y una por patrulla. */
    private function misiones(Unidad $tropa, Unidad $caminantes, Unidad $rovers): void
    {
        $crear = function (string $titulo, string $lugar, string $descripcion, Modalidad $modalidad, int $puntos, $lanza, $vence, array $unidades) {
            $mision = Mision::create([
                'titulo' => $titulo,
                'lugar' => $lugar,
                'descripcion' => $descripcion,
                'modalidad' => $modalidad,
                'puntos' => $puntos,
                'lanza_at' => $lanza,
                'vence_at' => $vence,
            ]);
            $mision->unidades()->attach(array_map(fn ($u) => $u->id, $unidades));
        };

        $todas = [$tropa, $caminantes, $rovers];

        $crear('Bienvenida al JOTA-JOTI', 'Entrada principal', 'Sacate una foto con tu uniforme y contanos de dónde venís.', Modalidad::Individual, 10, now()->subHours(2), null, $todas);
        $crear('Nudos en equipo', 'Campo de patrullas', 'Armen entre toda la patrulla un nudo de ocho y mandá la foto del equipo.', Modalidad::Patrulla, 50, now()->subHour(), now()->addHours(5), [$tropa, $caminantes]);
        $crear('Bitácora Rover', 'Fogón Rover', 'Escribí una reflexión corta sobre tu servicio en este evento.', Modalidad::Individual, 40, now()->subMinutes(30), null, [$rovers]);
        $crear('Mensaje en clave', 'Carpa de dirigentes', 'Descifrá el mensaje secreto y mandá la respuesta.', Modalidad::Individual, 30, now()->addHours(3), null, $todas);
        $crear('Desafío de la mañana', 'Plaza central', 'Esta misión ya venció: sirve para probar el caso de misión vencida.', Modalidad::Individual, 20, now()->subHours(5), now()->subHour(), $todas);
    }

    /** Talleres (individuales o por equipo) y estaciones JOTA (siempre por equipo), con turnos y cupos. */
    private function actividades(Unidad $tropa, Unidad $caminantes, Unidad $rovers): void
    {
        $crear = function (TipoActividad $tipo, string $titulo, string $lugar, Modalidad $modalidad, int $puntos, array $unidades, array $turnos) {
            $actividad = Actividad::create(['tipo' => $tipo, 'titulo' => $titulo, 'lugar' => $lugar, 'modalidad' => $modalidad, 'puntos' => $puntos]);
            $actividad->unidades()->attach(array_map(fn ($u) => $u->id, $unidades));

            foreach ($turnos as [$desde, $hasta, $cupo]) {
                $actividad->turnos()->create(['inicio' => now()->modify($desde), 'fin' => now()->modify($hasta), 'cupo' => $cupo]);
            }
        };

        $todas = [$tropa, $caminantes, $rovers];

        $crear(TipoActividad::Taller, 'Taller de primeros auxilios', 'Enfermería', Modalidad::Individual, 20, $todas, [
            ['+1 hour', '+2 hours', 10], ['+3 hours', '+4 hours', 8], ['+5 hours', '+6 hours', null],
        ]);
        // Estaciones JOTA: siempre por equipo (los Rovers, como unidad) y cada horario es de un solo equipo (cupo 1).
        $crear(TipoActividad::EstacionJota, 'Estación JOTA: radioaficionados', 'Carpa de radio', Modalidad::Patrulla, 15, $todas, [
            ['+90 minutes', '+150 minutes', 1], ['+4 hours', '+5 hours', 1],
        ]);
        $crear(TipoActividad::EstacionJota, 'Estación JOTA: gran juego de patrullas', 'Cancha de fútbol', Modalidad::Patrulla, 60, $todas, [
            ['+2 hours', '+3 hours', 1], ['+4 hours', '+5 hours', 1],
        ]);
        $crear(TipoActividad::Taller, 'Taller Rover: liderazgo', 'Fogón Rover', Modalidad::Individual, 25, [$rovers], [
            ['+1 hour', '+3 hours', 6],
        ]);
    }

    private function usuario(Rol $rol, string $nombre, Grupo $grupo, ?Unidad $unidad = null, ?Patrulla $patrulla = null): User
    {
        $user = User::create([
            'ci' => (string) ++$this->ci,
            'name' => $nombre,
            'rol' => $rol,
            'grupo_id' => $grupo->id,
            'unidad_id' => $unidad?->id,
            'patrulla_id' => $patrulla?->id,
        ]);

        $user->forceFill(['pin_hash' => $this->pinHash, 'activado_at' => now()])->save();

        return $user;
    }
}
