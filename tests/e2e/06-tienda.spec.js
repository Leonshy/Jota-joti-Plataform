import { expect, test } from '@playwright/test';
import { saldo, sesionDe, urlQrCobro } from './apoyo.mjs';

async function teclear(page, monto) {
    for (const d of String(monto)) await page.locator(`[data-tecla="${d}"]`).click();
}

test.describe('Tienda: cobrar con QR, pagar, devolver', () => {
    test('la tienda genera el cobro, la persona paga y la tienda ve quién pagó', async ({ browser }) => {
        const { page: tienda } = await sesionDe(browser, '4000048');
        const { page: ana } = await sesionDe(browser, '4000001'); // tiene 10 LisCoins de su misión aprobada
        const antes = saldo('4000001');

        await tienda.goto('/tienda');
        await teclear(tienda, 5);
        await expect(tienda.locator('[data-monto]')).toHaveText('5');
        await tienda.getByRole('button', { name: 'Generar cobro' }).click();
        await expect(tienda.getByText('Mostrale este código al cliente')).toBeVisible();
        await expect(tienda.locator('[data-qr-contenido] svg')).toBeVisible();
        await expect(tienda.locator('[data-cuenta-regresiva]')).toHaveText(/[01]:\d\d/);

        // La persona abre el cobro, ve el monto y paga.
        await ana.goto(urlQrCobro());
        await expect(ana.getByText('Vas a pagar')).toBeVisible();
        await expect(ana.getByText(/a Tienda JOTA/)).toBeVisible();
        await ana.getByRole('button', { name: 'Pagar' }).click();
        await expect(ana.getByRole('heading', { name: '¡Pago listo!' })).toBeVisible();
        expect(saldo('4000001').jlis).toBe(antes.jlis - 5);

        // La pantalla de la tienda se entera sola.
        await expect(tienda.getByRole('heading', { name: /Pagado por Águilas 1/ })).toBeVisible({ timeout: 15_000 });

        // Pagar otra vez el mismo cobro no descuenta de nuevo.
        await ana.goto(urlQrCobro());
        await expect(ana.getByRole('heading', { name: 'Este cobro ya fue pagado' })).toBeVisible();
        expect(saldo('4000001').jlis).toBe(antes.jlis - 5);
    });

    test('sin saldo suficiente avisa cuánto falta y no descuenta', async ({ browser }) => {
        const { page: tienda } = await sesionDe(browser, '4000048');
        const { page: beto } = await sesionDe(browser, '4000020'); // Cóndores: sin LisCoins

        await tienda.goto('/tienda');
        await teclear(tienda, 400);
        await tienda.getByRole('button', { name: 'Generar cobro' }).click();
        await expect(tienda.locator('[data-qr-contenido] svg')).toBeVisible();

        await beto.goto(urlQrCobro());
        await expect(beto.getByText(/No te alcanzan los LisCoins\. Te faltan 400 LisCoins/)).toBeVisible();
        await expect(beto.getByRole('button', { name: 'Pagar' })).toBeDisabled();
        expect(saldo('4000020').jlis).toBe(0);
    });

    test('cancelar el cobro hace que el QR deje de servir', async ({ browser }) => {
        const { page: tienda } = await sesionDe(browser, '4000048');
        const { page: ana } = await sesionDe(browser, '4000001');
        const antes = saldo('4000001');

        await tienda.goto('/tienda');
        await teclear(tienda, 1);
        await tienda.getByRole('button', { name: 'Generar cobro' }).click();
        await expect(tienda.locator('[data-qr-contenido] svg')).toBeVisible();
        const qr = urlQrCobro();
        await tienda.getByRole('button', { name: 'Cancelar cobro' }).click();
        await expect(tienda.getByRole('button', { name: 'Generar cobro' })).toBeVisible();

        await ana.goto(qr);
        await expect(ana.getByRole('heading', { name: 'El cobro venció' })).toBeVisible();
        expect(saldo('4000001').jlis).toBe(antes.jlis);
    });

    test('devolver respeta el tope de lo gastado y suma solo LisCoins', async ({ browser }) => {
        const { page: tienda } = await sesionDe(browser, '4000048');
        const antes = saldo('4000001'); // gastó 5 en la tienda

        await tienda.goto('/tienda/devolver');
        await tienda.getByLabel('CI o nombre').fill('4000001');
        await tienda.getByRole('button', { name: /Águilas 1/ }).click();
        await expect(tienda.getByText('Se pueden devolver hasta')).toBeVisible();

        await tienda.getByLabel('Monto a devolver').fill('50');
        await tienda.getByLabel('Motivo (obligatorio)').fill('Producto en mal estado');
        await tienda.getByRole('button', { name: 'Devolver', exact: true }).click();
        await expect(tienda.getByText(/Solo se pueden devolver hasta 5 LisCoins/)).toBeVisible();
        expect(saldo('4000001')).toEqual(antes);

        await tienda.getByLabel('Monto a devolver').fill('2');
        await tienda.getByRole('button', { name: 'Devolver', exact: true }).click();
        await expect(tienda.getByText('Devolución hecha')).toBeVisible();
        expect(saldo('4000001')).toEqual({ puntos: antes.puntos, jlis: antes.jlis + 2 });
    });

    test('el historial del puesto y el de la persona reflejan los movimientos', async ({ browser }) => {
        const { page: tienda } = await sesionDe(browser, '4000048');
        const { page: ana } = await sesionDe(browser, '4000001');

        await tienda.goto('/tienda/historial');
        await expect(tienda.getByText('Total cobrado')).toBeVisible();
        await expect(tienda.getByText('Águilas 1').first()).toBeVisible();

        await ana.goto('/app/historial');
        await expect(ana.getByText('Compra en la tienda', { exact: true })).toBeVisible();
        await expect(ana.getByText('Devolución de la tienda', { exact: true })).toBeVisible();
        await expect(ana.getByText('Misión aprobada', { exact: true }).first()).toBeVisible();
        await ana.getByRole('button', { name: 'Puntos' }).click();
        await expect(ana.getByText('Compra en la tienda', { exact: true })).toHaveCount(0);
    });
});
