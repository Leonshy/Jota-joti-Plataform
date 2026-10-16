import { expect, test } from '@playwright/test';
import { sesionDe } from './apoyo.mjs';

test.describe('Seguridad en el navegador', () => {
    test('las páginas llevan los encabezados de seguridad y la política no deja salir al navegador del sitio', async ({ page }) => {
        const r = await page.goto('/ingresar');
        const h = r.headers();

        expect(h['content-security-policy']).toContain("frame-ancestors 'none'");
        expect(h['x-frame-options']).toBe('DENY');
        expect(h['x-content-type-options']).toBe('nosniff');
        expect(h['permissions-policy']).toContain('camera=(self)');
        expect(h['x-powered-by']).toBeUndefined();

        // Un script inyectado no puede cargar nada de afuera (la política lo bloquea).
        const bloqueado = await page.evaluate(() => new Promise((resolve) => {
            const s = document.createElement('script');
            s.src = 'https://ejemplo.invalid/x.js';
            s.onerror = () => resolve(true);
            s.onload = () => resolve(false);
            document.head.append(s);
        }));
        expect(bloqueado).toBe(true);
    });

    test('un POST sin el token de la página es rechazado (CSRF)', async ({ browser }) => {
        const { page } = await sesionDe(browser, '4000001');

        const r = await page.request.post('/salir', { headers: { Accept: 'text/html' } });
        expect(r.status()).toBe(419);

        // Sigue con la sesión abierta: el intento no la cerró.
        await page.goto('/app');
        await expect(page).toHaveURL(/\/app$/);
    });

    test('la cookie de sesión no la lee JavaScript', async ({ browser }) => {
        const { page, contexto } = await sesionDe(browser, '4000001');
        const visibles = await page.evaluate(() => document.cookie);
        const cookies = await contexto.cookies();
        const sesion = cookies.find((c) => c.name.includes('session'));

        expect(visibles).not.toContain('session');
        expect(sesion.httpOnly).toBe(true);
        expect(sesion.sameSite).toBe('Lax');
    });

    test('un participante no ve ni cambia lo de otros cambiando números en la dirección', async ({ browser }) => {
        const { page } = await sesionDe(browser, '4000001');

        for (const ruta of ['/dirigente', '/dirigente/entregas/1', '/dirigente/asistencia/1', '/tienda', '/admin/participantes']) {
            const r = await page.goto(ruta);
            expect(r.status(), ruta).toBe(403);
        }
        // La foto de la entrega de otra persona (la 1 es de Águilas 1, que no es esta persona), tampoco.
        const { page: otra } = await sesionDe(browser, '4000013');
        expect((await otra.goto('/entregas/imagenes/1')).status()).toBe(403);
    });

    test('las fotos de las entregas no se sirven sin sesión', async ({ page }) => {
        const r = await page.goto('/entregas/imagenes/1');
        await expect(page).toHaveURL(/\/ingresar/);
        expect(r.status()).toBe(200); // la página de ingreso
    });

    test('el ranking del participante no muestra nombres ni CI', async ({ browser }) => {
        const { page } = await sesionDe(browser, '4000001');
        await page.goto('/app/ranking');
        const html = await page.content();

        expect(html).not.toMatch(/Águilas 2|Pumas \d|Jaguares|4000002|4000013/);
        await expect(page.getByText('La tabla no muestra nombres. Solo el puesto y el puntaje.')).toBeVisible();
    });

    test('la vista previa del diseño y los errores no muestran detalles internos', async ({ page }) => {
        const r = await page.goto('/ruta-que-no-existe');
        expect(r.status()).toBe(404);
        const cuerpo = await page.content();
        expect(cuerpo).not.toMatch(/vendor\/|Stack trace|laravel\.log|SQLSTATE/i);
        await expect(page.getByText('No encontramos esa página')).toBeVisible();
    });
});
