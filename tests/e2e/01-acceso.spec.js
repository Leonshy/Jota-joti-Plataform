import { expect, test } from '@playwright/test';
import { entrar, PIN, salir } from './apoyo.mjs';

test.describe('Acceso', () => {
    test('un participante sin PIN lo crea con su CI y entra con la bienvenida', async ({ page }) => {
        await page.goto('/activar');
        await page.getByLabel('Tu número de CI').fill('5000001');
        await page.getByRole('button', { name: 'Continuar' }).click();

        await page.getByLabel('Elegí tu PIN').fill('4321');
        await page.getByLabel('Repetí tu PIN').fill('4321');
        await page.getByRole('button', { name: 'Crear mi PIN y entrar' }).click();

        await expect(page).toHaveURL(/\/app$/);
        await expect(page.getByText('¡Listo, Nuevo!')).toBeVisible();
        await salir(page);

        await entrar(page, '5000001', '4321');
    });

    test('un PIN incorrecto avisa, se puede reintentar sin límite y después entra', async ({ page }) => {
        await page.goto('/ingresar');
        await page.getByLabel('Tu número de CI').fill('4000001');

        for (let i = 0; i < 6; i++) {
            await page.getByLabel('Tu PIN').fill('0000');
            await page.getByRole('button', { name: 'Ingresar' }).click();
            await expect(page.getByText('PIN incorrecto. Probá de nuevo.')).toBeVisible();
            await expect(page.getByLabel('Tu PIN')).toHaveValue('');
        }

        await page.getByLabel('Tu PIN').fill(PIN);
        await page.getByRole('button', { name: 'Ingresar' }).click();
        await expect(page).toHaveURL(/\/app$/);
    });

    test('un CI que no está cargado avisa', async ({ page }) => {
        await page.goto('/ingresar');
        await page.getByLabel('Tu número de CI').fill('9999999');
        await page.getByLabel('Tu PIN').fill('1234');
        await page.getByRole('button', { name: 'Ingresar' }).click();

        await expect(page.getByText('Ese CI no está cargado. Hablá con un dirigente.')).toBeVisible();
    });

    test('quien todavía no tiene PIN y prueba por Ingresar pasa a crearlo con su CI ya escrito', async ({ page }) => {
        await page.goto('/ingresar');
        await page.getByLabel('Tu número de CI').fill('5000002');
        await page.getByLabel('Tu PIN').fill('1234');
        await page.getByRole('button', { name: 'Ingresar' }).click();

        await expect(page).toHaveURL(/\/activar\?ci=5000002/);
        await expect(page.getByLabel('Tu número de CI')).toHaveValue('5000002');
    });

    test('cada rol cae en su pantalla', async ({ page }) => {
        const destinos = [['4000001', /\/app$/], ['4000045', /\/dirigente$/], ['4000048', /\/tienda$/], ['4000049', /\/admin/]];

        for (const [ci, destino] of destinos) {
            await page.context().clearCookies();
            await entrar(page, ci, PIN, destino);
        }
    });

    test('al pedir una pantalla sin sesión, ingresa y vuelve a esa pantalla', async ({ page }) => {
        await page.goto('/app/misiones');
        await expect(page).toHaveURL(/\/ingresar/);
        await page.getByLabel('Tu número de CI').fill('4000001');
        await page.getByLabel('Tu PIN').fill(PIN);
        await page.getByRole('button', { name: 'Ingresar' }).click();

        await expect(page).toHaveURL(/\/app\/misiones$/);
    });

    test('cerrar sesión funciona y las pantallas dejan de abrirse', async ({ page }) => {
        await entrar(page, '4000001');
        await salir(page);
        await page.goto('/app');
        await expect(page).toHaveURL(/\/ingresar/);
    });
});
