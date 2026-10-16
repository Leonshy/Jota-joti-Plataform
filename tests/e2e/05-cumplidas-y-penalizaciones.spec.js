import { expect, test } from '@playwright/test';
import { saldo, sesionDe } from './apoyo.mjs';

test.describe('Cumplidas y penalizaciones (dirigente)', () => {
    test('marcar una patrulla como cumplida acredita a la patrulla y a cada integrante, una sola vez', async ({ browser }) => {
        const { page: dir } = await sesionDe(browser, '4000045');
        const antes = saldo('4000009'); // Zorros 1 (integrante)

        await dir.goto('/dirigente/cumplimientos');
        await dir.getByRole('link', { name: /gran juego de patrullas/ }).click();
        const zorros = dir.getByRole('listitem').filter({ hasText: 'Zorros' });
        await zorros.getByRole('button', { name: 'Cumplida', exact: true }).click();
        await expect(dir.getByText('¿Marcar a Zorros como cumplida?')).toBeVisible();
        await dir.getByRole('button', { name: 'Sí, cumplida' }).click();

        await expect(zorros.getByText('Definitivo: ya se acreditó.')).toBeVisible();
        const despues = saldo('4000009');
        expect(despues.jlis).toBeGreaterThan(antes.jlis);

        // Ya no ofrece marcarla de nuevo.
        await expect(zorros.getByRole('button', { name: 'Cumplida', exact: true })).toHaveCount(0);

        // Una patrulla «no cumplida» se puede corregir a cumplida.
        const pumas = dir.getByRole('listitem').filter({ hasText: 'Pumas' });
        await pumas.getByRole('button', { name: 'No cumplida', exact: true }).click();
        await expect(pumas.getByText('No cumplida').first()).toBeVisible();
        await expect(pumas.getByRole('button', { name: 'Corregir a cumplida' })).toBeVisible();
    });

    test('los Rovers cumplen la estación como una unidad: cada Rover recibe los puntos completos', async ({ browser }) => {
        const { page: dir } = await sesionDe(browser, '4000047');
        const antes = [saldo('4000043'), saldo('4000044')];

        await dir.goto('/dirigente/cumplimientos');
        await dir.getByRole('link', { name: /gran juego de patrullas/ }).click();
        await expect(dir.getByText('Toda la unidad Rovers')).toBeVisible();
        await dir.getByRole('button', { name: 'Cumplida', exact: true }).click();
        await expect(dir.getByText(/Cada integrante recibe 60 puntos y 60 LisCoins/)).toBeVisible();
        await dir.getByRole('button', { name: 'Sí, cumplida' }).click();
        await expect(dir.getByText('Definitivo: ya se acreditó.')).toBeVisible();

        for (const [i, ci] of ['4000043', '4000044'].entries()) {
            expect(saldo(ci)).toEqual({ puntos: antes[i].puntos + 60, jlis: antes[i].jlis + 60 });
        }
    });

    test('penalizar resta puntos pero no LisCoins, y anular los devuelve', async ({ browser }) => {
        const { page: dir } = await sesionDe(browser, '4000045');
        const antes = saldo('4000009');

        await dir.goto('/dirigente/penalizaciones');
        await dir.getByLabel('Buscá por nombre o CI').fill('Zorros 2');
        await dir.getByRole('button', { name: /Zorros 2/ }).click();
        await dir.getByLabel('Cantidad de puntos a restar').fill('5');
        await dir.getByLabel('Motivo (obligatorio)').fill('Llegó tarde a formar');
        await dir.getByRole('button', { name: 'Aplicar penalización' }).click();

        await expect(dir.getByText('Penalización aplicada')).toBeVisible();
        expect(saldo('4000009')).toEqual({ puntos: antes.puntos - 5, jlis: antes.jlis });

        // Anular exige motivo y devuelve los puntos.
        await dir.getByRole('button', { name: /Anular: Zorros 2/ }).click();
        await dir.getByRole('button', { name: 'Anular', exact: true }).last().click();
        await expect(dir.getByText('Tenés que indicar el motivo.')).toBeVisible();
        await dir.getByLabel('Motivo de la anulación (obligatorio)').fill('Me equivoqué de persona');
        await dir.getByRole('button', { name: 'Anular', exact: true }).last().click();
        await expect(dir.getByText('Anulada').first()).toBeVisible();
        expect(saldo('4000009').puntos).toBe(antes.puntos);
    });

    test('valida cantidad y motivo', async ({ browser }) => {
        const { page: dir } = await sesionDe(browser, '4000045');
        await dir.goto('/dirigente/penalizaciones');
        await dir.getByRole('button', { name: 'Aplicar penalización' }).click();
        await expect(dir.getByText('Elegí a la persona.')).toBeVisible();
    });

    test('el dirigente de Tropa no encuentra a participantes de otra unidad', async ({ browser }) => {
        const { page: dir } = await sesionDe(browser, '4000045');
        await dir.goto('/dirigente/penalizaciones');
        await dir.getByLabel('Buscá por nombre o CI').fill('Yaguaretés');
        await expect(dir.getByText('No encontramos a nadie con ese nombre o CI en tu unidad.')).toBeVisible();
    });
});
