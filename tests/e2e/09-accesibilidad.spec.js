import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { sesionDe } from './apoyo.mjs';

/*
 * Accesibilidad AA en el celular (360 px): axe-core con las reglas de WCAG 2.0/2.1/2.2 nivel A y AA, sobre las
 * pantallas de cada rol, más dos revisiones propias: que nada se desborde a lo ancho (se leería cortado o con
 * desplazamiento horizontal) y que los botones y enlaces se puedan tocar bien (mínimo 44 px).
 */
const ETIQUETAS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

const PANTALLAS = {
    '4000001': ['/app', '/app/misiones', '/app/ranking', '/app/actividades', '/app/actividades/reservas', '/app/historial', '/app/escanear'],
    '4000045': ['/dirigente', '/dirigente/ranking', '/dirigente/reservas', '/dirigente/asistencia', '/dirigente/cumplimientos', '/dirigente/penalizaciones'],
    '4000048': ['/tienda', '/tienda/devolver', '/tienda/historial'],
};

async function auditar(page, ruta) {
    await page.goto(ruta);
    await page.waitForLoadState('networkidle');

    const { violations } = await new AxeBuilder({ page }).withTags(ETIQUETAS).analyze();
    const graves = violations.filter((v) => ['serious', 'critical', 'moderate'].includes(v.impact));

    const resumen = graves.map((v) => `${v.id} (${v.impact}): ${v.help} → ${v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(' | ')}`);
    expect(resumen, `${ruta}: violaciones de accesibilidad`).toEqual([]);

    // Nada se desborda a lo ancho.
    const desborde = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(desborde, `${ruta}: desborde horizontal`).toBeLessThanOrEqual(0);

    // Objetivos táctiles: botones y enlaces visibles de al menos 44 px de alto o ancho (los enlaces dentro de texto, excluidos).
    const chicos = await page.evaluate(() => [...document.querySelectorAll('button:not([hidden]), a[href]:not([hidden]), [role=button]')]
        .filter((e) => e.offsetParent !== null && !e.closest('p') && !e.classList.contains('sr-only') && !e.closest('.sr-only'))
        .map((e) => ({ e, r: e.getBoundingClientRect() }))
        .filter(({ r }) => r.width > 0 && r.height > 0 && r.height < 44 && r.width < 44)
        .map(({ e }) => `${e.tagName.toLowerCase()} «${(e.textContent || e.getAttribute('aria-label') || '').trim().slice(0, 30)}»`));
    expect(chicos, `${ruta}: objetivos táctiles menores de 44 px`).toEqual([]);
}

test.describe('Accesibilidad AA en el celular', () => {
    for (const [ci, rutas] of Object.entries(PANTALLAS)) {
        test(`pantallas del usuario ${ci}`, async ({ browser }) => {
            const { page } = await sesionDe(browser, ci);

            for (const ruta of rutas) await auditar(page, ruta);
        });
    }

    test('pantallas de ingreso y activación (sin sesión)', async ({ page }) => {
        for (const ruta of ['/ingresar', '/activar']) await auditar(page, ruta);
    });

    test('pantallas con estados: errores, avisos y resultados', async ({ browser }) => {
        const { page } = await sesionDe(browser, '4000001');

        // Entrega con error
        await page.goto('/app/misiones');
        await page.getByRole('link', { name: /Desafío de la mañana|Mensaje en clave|Bienvenida/ }).first().click();
        await page.getByRole('link', { name: /Entregar/ }).click();
        await page.getByRole('button', { name: 'Enviar entrega' }).click();
        await expect(page.getByText('Escribí algo o subí al menos una foto.')).toBeVisible();
        const { violations } = await new AxeBuilder({ page }).withTags(ETIQUETAS).analyze();
        expect(violations.filter((v) => ['serious', 'critical', 'moderate'].includes(v.impact)).map((v) => v.id)).toEqual([]);

        // Ayuda sin cámara (la cámara no está disponible en el navegador de pruebas)
        await auditar(page, '/app/escanear');
    });

    test('pantalla pública del QR y página de error', async ({ browser, page }) => {
        const { page: dir } = await sesionDe(browser, '4000045');
        await dir.goto('/dirigente/asistencia');
        await dir.getByText('Taller de primeros auxilios').click();
        await expect(dir.locator('[data-qr-contenido] svg')).toBeVisible();
        const publica = await dir.getByRole('link', { name: 'Mostrar QR en pantalla completa' }).getAttribute('href');
        await auditar(page, publica);
        await auditar(page, '/ruta-que-no-existe');
    });
});
