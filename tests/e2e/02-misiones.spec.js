import { expect, test } from '@playwright/test';
import { FOTO, imagenPesada, saldo, sesionDe } from './apoyo.mjs';

test.describe('Misiones: entregar → revisar → puntos', () => {
    test('el participante entrega con texto y foto, y el dirigente aprueba', async ({ browser }) => {
        const { page: ana } = await sesionDe(browser, '4000001');
        const { page: dir } = await sesionDe(browser, '4000045');

        await ana.goto('/app/misiones');
        await ana.getByRole('link', { name: /Bienvenida al JOTA-JOTI/ }).click();
        await ana.getByRole('link', { name: 'Entregar' }).click();

        await ana.getByLabel('Contanos qué hiciste').fill('Soy de Asunción, vengo con mi patrulla.');
        await ana.locator('input[type=file]').setInputFiles({ name: 'foto.png', mimeType: 'image/png', buffer: FOTO });
        await expect(ana.getByText('Fotos (1 de 4)')).toBeVisible();
        await ana.getByRole('button', { name: 'Enviar entrega' }).click();
        await expect(ana.getByRole('heading', { name: '¡Entrega enviada!' })).toBeVisible();

        // Aparece como «esperando revisión» y no se puede entregar otra vez.
        await ana.getByRole('link', { name: 'Ver la misión' }).click();
        await expect(ana.getByText('Esperando revisión').first()).toBeVisible();
        await expect(ana.getByRole('link', { name: 'Entregar' })).toHaveCount(0);

        // El dirigente la ve en su cola, con la foto, y la aprueba.
        await dir.goto('/dirigente');
        await expect(dir.getByText('1 por revisar')).toBeVisible();
        await dir.getByRole('link', { name: /Águilas 1/ }).click();
        await expect(dir.getByText('Soy de Asunción, vengo con mi patrulla.')).toBeVisible();
        await expect(dir.locator('img[src*="/entregas/imagenes/"]')).toHaveCount(1);
        await dir.getByRole('button', { name: 'Aprobar' }).click();
        await expect(dir.getByRole('heading', { name: 'Aprobada' })).toBeVisible();

        // El participante ve el estado y los saldos nuevos.
        await ana.goto('/app/misiones');
        await expect(ana.getByText('Aprobada').first()).toBeVisible();
        expect(saldo('4000001')).toEqual({ puntos: 10, jlis: 10 });
        await ana.goto('/app');
        await expect(ana.getByText('Mis puntos')).toBeVisible();
    });

    test('un rechazo muestra el motivo y se puede volver a entregar', async ({ browser }) => {
        const { page: ana } = await sesionDe(browser, '4000002');
        const { page: dir } = await sesionDe(browser, '4000045');

        await ana.goto('/app/misiones');
        await ana.getByRole('link', { name: /Mensaje en clave/ }).click();
        await ana.getByRole('link', { name: 'Entregar' }).click();
        await ana.getByLabel('Contanos qué hiciste').fill('Mi respuesta');
        await ana.getByRole('button', { name: 'Enviar entrega' }).click();
        await expect(ana.getByRole('heading', { name: '¡Entrega enviada!' })).toBeVisible();

        await dir.goto('/dirigente');
        await dir.getByRole('link', { name: /Águilas 2/ }).click();
        await dir.getByRole('button', { name: 'Rechazar' }).click();
        await expect(dir.getByRole('heading', { name: /Rechazar la entrega de/ })).toBeVisible();
        await dir.getByRole('button', { name: 'Rechazar' }).click(); // sin motivo: no deja
        await expect(dir.getByText('Tenés que indicar el motivo.')).toBeVisible();
        await dir.getByLabel('Motivo (obligatorio)').fill('Falta la respuesta completa');
        await dir.getByRole('button', { name: 'Rechazar' }).click();
        await expect(dir.getByRole('heading', { name: 'Rechazada' })).toBeVisible();

        await ana.goto('/app/misiones');
        await ana.getByRole('link', { name: /Mensaje en clave/ }).click();
        await expect(ana.getByText('Falta la respuesta completa')).toBeVisible();
        await expect(ana.getByRole('link', { name: 'Volver a entregar' })).toBeVisible();
        expect(saldo('4000002')).toEqual({ puntos: 0, jlis: 0 });
    });

    test('una misión por patrulla la entrega un integrante y la ven los demás', async ({ browser }) => {
        const { page: a } = await sesionDe(browser, '4000001');
        const { page: b } = await sesionDe(browser, '4000002');

        await a.goto('/app/misiones');
        await a.getByRole('link', { name: /Nudos en equipo/ }).click();
        await a.getByRole('link', { name: 'Entregar por la patrulla' }).click();
        await a.getByLabel('Contanos qué hiciste').fill('Lo hicimos entre todos');
        await a.getByRole('button', { name: 'Enviar entrega' }).click();
        await expect(a.getByRole('heading', { name: '¡Entrega enviada!' })).toBeVisible();

        await b.goto('/app/misiones');
        await b.getByRole('link', { name: /Nudos en equipo/ }).click();
        await expect(b.getByText(/La entregó Águilas, de tu patrulla/)).toBeVisible();
        await expect(b.getByRole('link', { name: 'Entregar por la patrulla' })).toHaveCount(0);
    });

    test('no se puede entregar vacío', async ({ browser }) => {
        const { page } = await sesionDe(browser, '4000013');
        await page.goto('/app/misiones');
        await page.getByRole('link', { name: /Mensaje en clave/ }).click();
        await page.getByRole('link', { name: 'Entregar' }).click();
        await page.getByRole('button', { name: 'Enviar entrega' }).click();

        await expect(page.getByText('Escribí algo o subí al menos una foto.')).toBeVisible();
    });

    test('una foto de celular pesada (5 MB) se sube igual aunque el servidor limite a 2 MB: se achica antes', async ({ browser }) => {
        const { page } = await sesionDe(browser, '4000007');

        await page.goto('/app/misiones');
        await page.getByRole('link', { name: /Bienvenida al JOTA-JOTI/ }).click();
        await page.getByRole('link', { name: 'Entregar' }).click();

        await page.locator('input[type=file]').setInputFiles(imagenPesada('jpg'));
        await expect(page.getByText('Fotos (1 de 4)')).toBeVisible();
        // Nada de «validation.uploaded» ni de mensajes en inglés.
        await expect(page.locator('[role=alert]')).toHaveCount(0);

        await page.getByLabel('Contanos qué hiciste').fill('Con una foto pesada.');
        await page.getByRole('button', { name: 'Enviar entrega' }).click();
        await expect(page.getByRole('heading', { name: '¡Entrega enviada!' })).toBeVisible();
    });
});
