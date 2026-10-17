<?php

namespace App\Console\Commands;

use App\Domain\Ledger\Reconciliador;
use App\Domain\Participantes\EliminacionNoPermitida;
use App\Domain\Participantes\EliminarUsuarioDePrueba as Eliminar;
use App\Domain\Participantes\ReglasParticipante;
use App\Models\User;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Elimina una cuenta de PRUEBA y todo lo que generó, revirtiendo lo que acreditó a su equipo. Es una excepción a la regla de
 * «los movimientos no se borran»: siempre con simulacro primero, respaldo previo y confirmación escribiendo el CI.
 */
#[AsCommand(name: 'jota:eliminar-usuario-de-prueba', description: 'Elimina una cuenta de prueba, sus registros y lo que acreditó a su equipo (con simulacro y respaldo)')]
class EliminarUsuarioDePrueba extends Command
{
    protected $signature = 'jota:eliminar-usuario-de-prueba {ci : CI de la cuenta de prueba}
        {--simulacro : Muestra qué se borraría y qué saldos se corregirían, sin cambiar nada}
        {--force : No pide escribir el CI para confirmar}
        {--sin-respaldo : No hace el respaldo de la base antes (solo para pruebas)}';

    public function handle(Eliminar $eliminar): int
    {
        $ci = ReglasParticipante::normalizarCi((string) $this->argument('ci'));
        $user = User::query()->where('ci', $ci)->first();

        if (! $user) {
            $this->error("No existe un usuario con el CI {$ci}.");

            return self::FAILURE;
        }

        try {
            $plan = $eliminar->plan($user);
        } catch (EliminacionNoPermitida $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->mostrar($plan);

        if ($plan['conflictos'] !== []) {
            $this->error('No se puede eliminar todavía:');
            foreach ($plan['conflictos'] as $c) {
                $this->line("  · {$c}");
            }

            return self::FAILURE;
        }

        if ($this->option('simulacro')) {
            $this->info('Simulacro: no se cambió nada.');

            return self::SUCCESS;
        }

        if (($previas = (new Reconciliador)->diferencias()) !== []) {
            $this->error(count($previas).' saldos ya no coinciden con sus movimientos. Corré `php artisan jota:reconciliar` y revisalo antes de eliminar.');

            return self::FAILURE;
        }

        if (! $this->option('force') && $this->ask("Esto BORRA a {$plan['persona']['nombre']} y todo lo que generó, y no se puede deshacer. Escribí su CI para confirmar") !== $ci) {
            $this->warn('Cancelado: el CI no coincide. No se cambió nada.');

            return self::FAILURE;
        }

        if (! $this->option('sin-respaldo')) {
            $this->line('Haciendo el respaldo de la base antes de borrar…');

            if (Artisan::call('backup:run', ['--only-db' => true]) !== 0) {
                $this->error('No se pudo hacer el respaldo. No se cambió nada.');

                return self::FAILURE;
            }
        }

        try {
            $eliminar($user);
        } catch (EliminacionNoPermitida $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        activity('purga')->withProperties(['ci' => $ci, 'nombre' => $plan['persona']['nombre'], 'registros' => $plan['registros'], 'movimientos' => $plan['movimientos'], 'correcciones' => $plan['correcciones']])
            ->log('Eliminó una cuenta de prueba y revirtió lo que acreditó');
        Log::warning('Cuenta de prueba eliminada', ['ci' => $ci, 'movimientos' => $plan['movimientos']]);

        $this->info("Listo: {$plan['persona']['nombre']} (CI {$ci}) eliminado y saldos corregidos. Los saldos coinciden con los movimientos.");

        return self::SUCCESS;
    }

    /** @param  array<string, mixed>  $plan */
    private function mostrar(array $plan): void
    {
        $p = $plan['persona'];
        $this->line("Cuenta: {$p['nombre']} · CI {$p['ci']} · patrulla: ".($p['patrulla'] ?? '—')." · {$p['puntos']} puntos · {$p['liscoins']} LisCoins");
        $this->table(['Registro', 'Cantidad'], collect($plan['registros'])->map(fn ($n, $k) => [str_replace('_', ' ', $k), $n])->values()->all());
        $this->table(['Movimientos que se borran', 'Cantidad'], [
            ['puntos individuales', $plan['movimientos']['puntos_individuales']],
            ['LisCoins', $plan['movimientos']['liscoins']],
            ['puntos de patrulla', $plan['movimientos']['puntos_patrulla']],
        ]);

        if ($plan['correcciones'] !== []) {
            $this->line('Saldos de OTROS que se corrigen (lo que esta cuenta les había acreditado):');
            $this->table(['Quién', 'Nombre', 'Puntos', 'LisCoins'], collect($plan['correcciones'])->map(fn ($c) => [$c['tipo'], $c['nombre'], sprintf('%+d', $c['puntos']), $c['liscoins'] === 0 ? '—' : sprintf('%+d', $c['liscoins'])])->all());
        } else {
            $this->line('No acreditó nada a otras personas ni a patrullas.');
        }
    }
}
