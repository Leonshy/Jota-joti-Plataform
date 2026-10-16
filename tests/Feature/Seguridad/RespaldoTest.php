<?php

use Illuminate\Console\Scheduling\Schedule;

it('programa el respaldo de la base cada hora, la limpieza diaria y el control contable', function () {
    $comandos = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command)->implode("\n");

    expect($comandos)->toContain('backup:run --only-db')
        ->toContain('backup:clean')
        ->toContain('backup:monitor')
        ->toContain('jota:reconciliar');
});

it('el respaldo es solo de la base, comprimido y cifrado por contraseña', function () {
    expect(config('backup.backup.source.files.include'))->toBe([])
        ->and(config('backup.backup.database_dump_compressor'))->not->toBeNull()
        ->and(config('backup.backup.encryption'))->toBe('default')
        ->and(file_get_contents(base_path('.env.production.example')))->toContain('BACKUP_ARCHIVE_PASSWORD=');
});

it('el verificador de producción exige la contraseña del respaldo', function () {
    config(['backup.backup.password' => null]);

    $this->artisan('jota:verificar-produccion')->expectsOutputToContain('El respaldo tiene contraseña')->run();
});
