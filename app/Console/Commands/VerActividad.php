<?php

namespace App\Console\Commands;

use App\Domain\Actividades\ChoqueDeHorario;
use App\Domain\Actividades\Reservar;
use App\Domain\Participantes\ReglasParticipante;
use App\Models\Actividad;
use App\Models\User;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Diagnóstico de talleres y estaciones: cómo están configurados (modalidad, unidades, turnos, cupos, reservas) y, con --ci,
 * qué respondería el sistema si esa persona intentara reservar cada turno. El intento se hace dentro de una transacción que SIEMPRE se
 * deshace: no queda ninguna reserva guardada.
 */
#[AsCommand(name: 'jota:ver-actividad', description: 'Muestra los turnos y cupos de un taller o estación y, con --ci, por qué una persona puede o no apuntarse (no guarda nada)')]
class VerActividad extends Command
{
    protected $signature = 'jota:ver-actividad {texto : Parte del título} {--ci= : CI de la persona a probar}';

    public function handle(): int
    {
        $texto = trim((string) $this->argument('texto'));
        $actividades = Actividad::query()
            ->where('titulo', 'like', '%'.addcslashes($texto, '%_\\').'%')
            ->with(['unidades:id,nombre', 'turnos' => fn ($q) => $q->withCount('reservas')->orderBy('inicio')])
            ->orderBy('titulo')->limit(10)->get();

        if ($actividades->isEmpty()) {
            $this->error("No hay actividades con «{$texto}» en el título.");

            return self::FAILURE;
        }

        $user = null;

        if ($this->option('ci') !== null) {
            $user = User::query()->with(['patrulla:id,nombre', 'unidad:id,nombre'])->where('ci', ReglasParticipante::normalizarCi((string) $this->option('ci')))->first();

            if (! $user) {
                $this->error('No existe una persona con ese CI.');

                return self::FAILURE;
            }

            $this->line("Persona: {$user->name} · unidad: ".($user->unidad?->nombre ?? '—').' · patrulla: '.($user->patrulla?->nombre ?? '—'));
        }

        foreach ($actividades as $a) {
            $this->newLine();
            $this->info("#{$a->id} {$a->titulo}");
            $this->line('  tipo: '.$a->tipo->value.' · modalidad: '.$a->modalidad->value.' · puntos: '.$a->puntos.' · lugar: '.($a->lugar ?? '—'));
            $this->line('  unidades: '.($a->unidades->pluck('nombre')->implode(', ') ?: 'NINGUNA'));

            $filas = $a->turnos->map(function ($t) use ($user, $a) {
                $fila = [
                    $t->id,
                    $t->inicio->format('d/m H:i').' – '.$t->fin->format('H:i'),
                    $t->cupo ?? 'sin tope',
                    $t->reservas_count,
                    $t->cupo === null ? '—' : max(0, $t->cupo - $t->reservas_count),
                    match (true) {
                        $t->haTerminado() => 'TERMINÓ',
                        $t->haComenzado() && $a->esEstacion() => 'YA EMPEZÓ (cerrado)',
                        $t->haComenzado() => 'en curso (aún se puede anotar)',
                        default => 'abierto',
                    },
                ];

                return $user ? [...$fila, $this->probar($user, $a, $t)] : $fila;
            })->all();

            $this->table(['Turno', 'Horario', 'Cupo', 'Reservas', 'Lugares', 'Estado', ...($user ? ['Si '.$user->name.' intenta apuntarse'] : [])], $filas);
        }

        return self::SUCCESS;
    }

    /** Intenta la reserva de verdad, con todas sus reglas, y la deshace siempre. */
    private function probar(User $user, Actividad $actividad, $turno): string
    {
        DB::beginTransaction();

        try {
            app(Reservar::class)($user, $turno->fresh('actividad'));

            return 'SÍ se puede';
        } catch (ChoqueDeHorario $e) {
            $c = $e->choques[0] ?? null;

            return 'NO: choca con «'.($c['actividad'] ?? '?').'» ('.($c ? $c['inicio']->format('H:i').'–'.$c['fin']->format('H:i') : '').')';
        } catch (Throwable $e) {
            return 'NO: '.class_basename($e).' — '.$e->getMessage();
        } finally {
            DB::rollBack();
        }
    }
}
