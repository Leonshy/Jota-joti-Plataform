<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Respaldo de la base cada hora (solo la base: las fotos se purgan aparte). Cifrado con BACKUP_ARCHIVE_PASSWORD.
Schedule::command('backup:run --only-db')->hourly()->withoutOverlapping(30);
Schedule::command('backup:clean')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('backup:monitor')->hourlyAt(30);

// Control contable cada hora durante el evento (CLAUDE.md / PLAN Fase 11).
Schedule::command('jota:reconciliar')->hourlyAt(10)->withoutOverlapping();
