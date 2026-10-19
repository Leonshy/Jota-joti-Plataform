import { expect, test } from '@playwright/test';
import { sesionDe } from './apoyo.mjs';

test.describe('Ranking: el dirigente ve nombres, el participante no', () => {
    test('el dirigente de la Tropa ve los nombres de las patrullas con su desglose; el participante ve su tabla sin nombres', async ({ browser }) => {
        const { page: dir } = await sesionDe(browser, '4000045');
        const { page: ana } = await sesionDe(browser, '4000001');

        await dir.goto('/dirigente/ranking');
        const tabla = dir.getByRole('table', { name: /^Ranking de/ }); // la de equipos (hay otra, la de personas)
        await expect(tabla.getByText('Águilas', { exact: true })).toBeVisible();
        await expect(tabla.getByText('Jaguares', { exact: true })).toBeVisible();
        await expect(tabla.getByText(/integrantes/).first()).toBeVisible();
        await expect(tabla.getByText(/Patrulla .* \+ individuales/).first()).toBeVisible();
        // Los nombres de las personas y los CI no aparecen: solo las patrullas.
        await expect(dir.getByText('4000001')).toHaveCount(0);
        const desborde = await dir.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        expect(desborde).toBeLessThanOrEqual(0);

        // Debajo, las personas con más puntos de la unidad: Puesto · Participante · Puntos (solo individuales), sin CI.
        await expect(dir.getByRole('heading', { name: 'Personas con más puntos' })).toBeVisible();
        const personas = dir.getByRole('table', { name: /Personas con más puntos/ });
        await expect(personas.getByRole('columnheader', { name: 'Participante' })).toBeVisible();
        await expect(personas.getByRole('columnheader', { name: 'Puntos' })).toBeVisible();
        await expect(personas.getByRole('row')).not.toHaveCount(1); // hay encabezado y filas con personas

        // El participante, anónimo: ningún nombre de patrulla ni de persona.
        await ana.goto('/app/ranking');
        await expect(ana.getByRole('table')).toBeVisible();
        await expect(ana.getByText('Águilas')).toHaveCount(0);
        await expect(ana.getByText('Jaguares')).toHaveCount(0);
        await expect(ana.getByText('Águilas 2')).toHaveCount(0);
        await expect(ana.getByText('Personas con más puntos')).toHaveCount(0);
    });
});
