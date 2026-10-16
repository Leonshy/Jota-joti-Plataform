<?php

namespace App\Console\Commands;

use App\Models\EntregaImagen;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Borra las fotos de las entregas después del evento (§4.9). Lo ejecuta el responsable del grupo.
 * Conserva los textos, las revisiones y todos los movimientos: solo desaparecen las imágenes.
 */
#[AsCommand(name: 'jota:purgar-entregas', description: 'Borra todas las fotos de las entregas (después del evento)')]
class PurgarEntregas extends Command
{
    protected $signature = 'jota:purgar-entregas {--simulacro : Muestra cuánto se borraría sin borrar nada} {--force : No pide confirmación}';

    public function handle(): int
    {
        $disco = Storage::disk('private');
        $archivos = array_merge($disco->allFiles('entregas'), $disco->allFiles('tmp'));
        $registros = EntregaImagen::query()->count();

        $this->line("Fotos registradas: {$registros}. Archivos en disco: ".count($archivos).'.');

        if ($this->option('simulacro')) {
            $this->info('Simulacro: no se borró nada.');

            return self::SUCCESS;
        }

        if ($registros === 0 && $archivos === []) {
            $this->info('No hay fotos para borrar.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Esto borra TODAS las fotos de las entregas y no se puede deshacer. ¿Seguimos?')) {
            $this->warn('Cancelado. No se borró nada.');

            return self::FAILURE;
        }

        $disco->deleteDirectory('entregas');
        $disco->deleteDirectory('tmp');
        EntregaImagen::query()->delete();

        activity('purga')->withProperties(['registros' => $registros, 'archivos' => count($archivos)])->log('Purgó las fotos de las entregas');
        Log::warning('Purga de fotos de entregas ejecutada', ['registros' => $registros, 'archivos' => count($archivos)]);

        $this->info("Listo: se borraron {$registros} fotos y ".count($archivos).' archivos.');

        return self::SUCCESS;
    }
}
