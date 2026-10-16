import { expect, test } from '@playwright/test';
import { sesionDe } from './apoyo.mjs';

test.describe('Administrador', () => {
    test('entra al panel y recorre las secciones principales', async ({ browser }) => {
        const contexto = await browser.newContext({ viewport: { width: 1280, height: 800 } });
        const page = await contexto.newPage();
        await page.goto('/admin/login');
        await page.getByLabel('Cédula (CI)').fill('4000049');
        await page.getByLabel('PIN o contraseña').fill('1234');
        await page.getByRole('button', { name: 'Entrar' }).click();
        await expect(page).toHaveURL(/\/admin$/);

        for (const [ruta, texto] of [
            ['/admin/participantes', /Participantes/],
            ['/admin/misiones', /Misiones/],
            ['/admin/actividades', /talleres y estaciones/i],
            ['/admin/entregas', /Validación de entregas|entregas/i],
            ['/admin/reservas', /Reservas/],
            ['/admin/movimientos-jlis', /LisCoins/],
            ['/admin/rankings', /Ranking/],
        ]) {
            await page.goto(ruta);
            await expect(page.getByRole('heading', { level: 1, name: texto })).toBeVisible();
        }

        // El menú tiene los accesos a las vistas de celular y el nombre nuevo de la moneda.
        await page.goto('/admin');
        await expect(page.getByRole('link', { name: 'Vista de dirigente (celular)' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Vista de tienda (celular)' })).toBeVisible();
        await expect(page.getByText('J-lis')).toHaveCount(0);
        await contexto.close();
    });

    test('puede usar las pantallas de dirigente sobre la unidad que elija, y la tienda', async ({ browser }) => {
        const { page } = await sesionDe(browser, '4000049');

        await page.goto('/dirigente');
        await expect(page.getByLabel('Unidad')).toBeVisible();
        await page.getByLabel('Unidad').selectOption({ label: 'Caminantes' });
        await expect(page.getByLabel('Unidad')).toHaveValue('2');
        await page.goto('/dirigente/asistencia');
        await expect(page.getByLabel('Unidad')).toHaveValue('2');

        await page.goto('/tienda');
        await expect(page.getByRole('button', { name: 'Generar cobro' })).toBeVisible();
    });

    test('los demás roles no entran al panel', async ({ browser }) => {
        for (const ci of ['4000001', '4000045', '4000048']) {
            const { page, contexto } = await sesionDe(browser, ci);
            const r = await page.goto('/admin/participantes');
            expect([403, 200].includes(r.status())).toBe(true);
            await expect(page.getByText('Escritorio')).toHaveCount(0);
            await expect(page.locator('table')).toHaveCount(0);
            await contexto.close();
        }
    });
});
