<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

it('muestra los trabajos fallidos con su motivo, sin el contenido del trabajo', function () {
    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default', 'failed_at' => now(),
        'payload' => json_encode(['displayName' => 'App\\Jobs\\ProcesarImagenEntrega', 'data' => ['secreto' => 'NO-DEBE-SALIR']]),
        'exception' => "Intervention\\Image\\Exceptions\\DecoderException: Imagen demasiado grande\n#0 /trace/linea.php",
    ]);

    Artisan::call('jota:cola');
    $salida = Artisan::output();

    expect($salida)->toContain('fallidos: 1')->toContain('ProcesarImagenEntrega')->toContain('Imagen demasiado grande')
        ->not->toContain('NO-DEBE-SALIR')->not->toContain('/trace/linea.php');
});

it('avisa cuando no hay fallidos', function () {
    Artisan::call('jota:cola');

    expect(Artisan::output())->toContain('No hay trabajos fallidos');
});
