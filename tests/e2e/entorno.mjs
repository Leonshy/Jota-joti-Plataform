import { execFileSync } from 'node:child_process';

/** Variables del entorno de pruebas: base propia, cola inmediata y sin el depurador. Se pasan al servidor y a artisan. */
export const ENTORNO = {
    APP_ENV: 'local',
    APP_DEBUG: 'false',
    APP_URL: 'http://127.0.0.1:8123',
    DB_DATABASE: 'jotajoti_e2e',
    QUEUE_CONNECTION: 'sync',
    CACHE_STORE: 'database',
    SESSION_DRIVER: 'database',
    LOG_CHANNEL: 'single',
    SEED_PIN: '1234',
};

export function php(args, opciones = {}) {
    return execFileSync('php', args, { env: { ...process.env, ...ENTORNO }, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'], ...opciones });
}

/** Corre código PHP dentro de la app de pruebas y devuelve lo que imprima (la última línea no vacía). */
export function tinker(codigo) {
    const salida = php(['artisan', 'tinker', '--execute', codigo]).trim().split('\n').filter(Boolean);

    return salida[salida.length - 1] ?? '';
}
