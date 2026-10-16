<?php

namespace App\Console\Commands;

use App\Domain\Ledger\Reconciliador;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

#[AsCommand(name: 'jota:reconciliar', description: 'Compara los saldos cacheados con los movimientos y reporta diferencias')]
class Reconciliar extends Command
{
    protected $signature = 'jota:reconciliar {--corregir : Reescribe los saldos cacheados con el valor calculado}';

    public function handle(Reconciliador $reconciliador): int
    {
        $diferencias = $reconciliador->diferencias();

        if ($diferencias === []) {
            $this->info('Sin diferencias: los saldos coinciden con los movimientos.');

            return self::SUCCESS;
        }

        $this->table(['Saldo', 'ID', 'Esperado', 'Cacheado'], $diferencias);

        if ($this->option('corregir')) {
            $this->warn($reconciliador->corregir().' saldos corregidos.');

            return self::SUCCESS;
        }

        $this->error(count($diferencias).' diferencias. Correr con --corregir para reescribirlas.');

        return self::FAILURE;
    }
}
