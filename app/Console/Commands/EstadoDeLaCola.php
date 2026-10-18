<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Solo lectura: cuántos trabajos esperan en la cola y cuáles fallaron y por qué (la primera línea del error). No muestra el
 * contenido de los trabajos, que puede llevar datos de personas.
 */
#[AsCommand(name: 'jota:cola', description: 'Muestra los trabajos pendientes y los fallidos de la cola (fotos), con el motivo del fallo')]
class EstadoDeLaCola extends Command
{
    protected $signature = 'jota:cola {--limite=10 : Cuántos fallidos mostrar}';

    public function handle(): int
    {
        $this->line('Trabajos esperando: '.DB::table('jobs')->count().' · fallidos: '.DB::table('failed_jobs')->count());

        $fallidos = DB::table('failed_jobs')->orderByDesc('id')->limit(max(1, (int) $this->option('limite')))->get(['id', 'queue', 'failed_at', 'payload', 'exception']);

        if ($fallidos->isEmpty()) {
            $this->info('No hay trabajos fallidos.');

            return self::SUCCESS;
        }

        $this->table(['#', 'Cuándo', 'Trabajo', 'Motivo (primera línea)'], $fallidos->map(fn ($f) => [
            $f->id,
            $f->failed_at,
            class_basename((string) (json_decode($f->payload, true)['displayName'] ?? '?')),
            Str::limit(trim(strtok((string) $f->exception, "\n")), 160),
        ])->all());

        return self::SUCCESS;
    }
}
