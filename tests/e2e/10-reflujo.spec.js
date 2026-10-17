import { expect, test } from '@playwright/test';
import { sesionDe } from './apoyo.mjs';

/*
 * WCAG 1.4.10 (reflujo): a 320 px de ancho no debe haber desplazamiento horizontal.
 * WCAG 1.4.4 (texto): con el texto al 200 % tampoco, y nada de lo que se puede tocar queda tapado por la barra de abajo.
 */
const PANTALLAS = {
    '4000001': ['/app', '/app/misiones', '/app/ranking', '/app/actividades', '/app/historial', '/app/contactos/jota', '/app/contactos/joti/nuevo', '/app/contactos/jota/nuevo'],
    '4000045': ['/dirigente', '/dirigente/asistencia', '/dirigente/cumplimientos', '/dirigente/penalizaciones'],
    '4000048': ['/tienda', '/tienda/devolver', '/tienda/historial'],
};

for (const [ci, rutas] of Object.entries(PANTALLAS)) {
    test(`a 320 px y con texto al 200 % no se desborda (${ci})`, async ({ browser }) => {
        const { page } = await sesionDe(browser, ci);
        await page.setViewportSize({ width: 320, height: 568 });

        for (const ruta of rutas) {
            await page.goto(ruta);
            await page.addStyleTag({ content: 'html { font-size: 200% !important; }' });
            await page.waitForTimeout(150);

            const desborde = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
            expect(desborde, `${ruta}: desborde horizontal con texto al 200 %`).toBeLessThanOrEqual(0);

            // El contenido llega hasta el final: se puede bajar hasta el último elemento sin que la barra fija lo tape.
            const tapado = await page.evaluate(() => {
                const barra = document.querySelector('nav.fixed');
                const ultimo = [...document.querySelectorAll('main > *:not([hidden])')].pop();
                if (!barra || !ultimo) return 0;
                window.scrollTo(0, document.body.scrollHeight);
                return Math.max(0, ultimo.getBoundingClientRect().bottom - barra.getBoundingClientRect().top);
            });
            expect(tapado, `${ruta}: el último elemento queda tapado por la barra`).toBeLessThanOrEqual(1);
        }
    });
}
