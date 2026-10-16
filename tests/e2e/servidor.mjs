import { spawn } from 'node:child_process';
import { resolve } from 'node:path';
import { ENTORNO, php } from './entorno.mjs';

// Base limpia con los datos de prueba del seeder, más un participante que todavía no creó su PIN.
php(['artisan', 'migrate:fresh', '--seed', '--force']);
php(['artisan', 'tinker', '--execute', `
    $u = new App\\Models\\User(['ci' => '5000001', 'name' => 'Nuevo Participante', 'rol' => 'beneficiario', 'unidad_id' => 1, 'patrulla_id' => 1, 'grupo_id' => 1]);
    $u->save();
    $v = new App\\Models\\User(['ci' => '5000002', 'name' => 'Otro Participante', 'rol' => 'beneficiario', 'unidad_id' => 1, 'patrulla_id' => 2, 'grupo_id' => 1]);
    $v->save();

    // Todas las misiones (menos la vencida) ya lanzadas y abiertas, para no depender de la hora a la que se corra la prueba.
    App\\Models\\Mision::whereIn('id', [1, 2, 3, 4])->update(['lanza_at' => now()->subHour(), 'vence_at' => null, 'cerrada_at' => null]);
    // Turnos siempre en el futuro (en minutos desde ahora). El 1 (taller) y el 4 (estación) se pisan a propósito.
    $horarios = [1 => [60, 120], 2 => [180, 240], 3 => [300, 360], 4 => [90, 150], 5 => [300, 360], 6 => [120, 180], 7 => [240, 300], 8 => [60, 180]];
    foreach ($horarios as $id => [$desde, $hasta]) {
        App\\Models\\Turno::whereKey($id)->update(['inicio' => now()->addMinutes($desde), 'fin' => now()->addMinutes($hasta)]);
    }
`]);

// Varios procesos de PHP, como un servidor real (el servidor de desarrollo por defecto atiende de a una petición).
const servidor = spawn('php', ['-S', '127.0.0.1:8123', '-t', resolve('public'), resolve('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
    cwd: resolve('public'), // el enrutador de Laravel busca index.php desde public/
    env: { ...process.env, ...ENTORNO, PHP_CLI_SERVER_WORKERS: '8' },
    stdio: ['ignore', 'ignore', 'pipe'],
});

servidor.stderr.on('data', (d) => {
    const linea = String(d);
    if (/Fatal|Error|Exception/i.test(linea)) process.stderr.write(linea);
});

const cerrar = () => { servidor.kill(); process.exit(0); };
process.on('SIGTERM', cerrar);
process.on('SIGINT', cerrar);
servidor.on('exit', () => process.exit(0));
