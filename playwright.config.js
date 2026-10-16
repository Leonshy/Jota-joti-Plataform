import { defineConfig, devices } from '@playwright/test';

/*
 * QA de punta a punta (Fase 10): navegador real con el tamaño de un celular (360 px), contra un servidor y una base
 * PROPIOS de pruebas (jotajoti_e2e), nunca contra la base de desarrollo. Se arranca solo: `npm run e2e`.
 */
export default defineConfig({
    testDir: 'tests/e2e',
    timeout: 90_000,
    expect: { timeout: 10_000 },
    workers: 1, // los pasos dependen del estado que dejan los anteriores (misión aprobada → saldo → pago)
    fullyParallel: false,
    retries: 0,
    reporter: [['list'], ['html', { open: 'never', outputFolder: 'storage/app/e2e-informe' }]],
    outputDir: 'storage/app/e2e-resultados',
    use: {
        ...devices['Pixel 5'],
        viewport: { width: 360, height: 740 },
        baseURL: 'http://127.0.0.1:8123',
        locale: 'es-PY',
        timezoneId: 'America/Asuncion',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'node tests/e2e/servidor.mjs',
        url: 'http://127.0.0.1:8123/up',
        reuseExistingServer: false,
        timeout: 180_000,
        stdout: 'ignore',
        stderr: 'pipe',
    },
});
