import { expect, test } from '@playwright/test';
import { FOTO, sesionDe } from './apoyo.mjs';
import { tinker } from './entorno.mjs';

/** Define el puntaje de los contactos como lo haría el admin desde el panel. */
function definirPuntajes() {
    tinker('app(App\\Domain\\Contactos\\Puntaje::class)->guardar(["jota_local" => 10, "jota_exterior" => 30, "joti_local" => 5, "joti_exterior" => 15], App\\Models\\User::where("rol", "admin")->first()); echo "ok";');
}

const puntosDe = (ci) => Number(tinker(`echo App\\Models\\User::where("ci", "${ci}")->first()->puntos;`));
const puntosPatrulla = (ci) => Number(tinker(`echo App\\Models\\User::where("ci", "${ci}")->first()->patrulla->puntos;`));

test.describe('Registro JOTA y JOTI: cargar → validar → puntos', () => {
    test('el inicio tiene las dos tarjetas y JOTI se carga con captura, se aprueba y suma a la persona', async ({ browser }) => {
        definirPuntajes();
        const { page: ana } = await sesionDe(browser, '4000003');
        const { page: dir } = await sesionDe(browser, '4000045');
        const antes = puntosDe('4000003');

        await ana.goto('/app');
        await expect(ana.getByRole('link', { name: /Registro JOTA/ })).toBeVisible();
        await ana.getByRole('link', { name: /Registro JOTI/ }).click();
        await ana.getByRole('link', { name: 'Registrar contacto' }).click();

        await ana.getByLabel('Grupo scout').fill('Grupo Scout Lima 1');
        await ana.getByLabel('Ciudad').fill('Lima');
        await ana.getByLabel('Unidad', { exact: true }).fill('Tropa');
        await ana.getByLabel('Otro país').check();
        await ana.getByLabel('¿Cuál?').fill('Perú');
        await ana.locator('input[type=file]').setInputFiles({ name: 'chat.png', mimeType: 'image/png', buffer: FOTO });
        await expect(ana.getByRole('button', { name: 'Quitar la captura' })).toBeVisible();
        await ana.getByRole('button', { name: 'Enviar contacto' }).click();
        await expect(ana.getByRole('heading', { name: '¡Contacto enviado!' })).toBeVisible();

        await ana.getByRole('link', { name: 'Ver mis contactos' }).click();
        await expect(ana.getByText('Lima, Perú')).toBeVisible();
        await expect(ana.getByText('Por revisar').first()).toBeVisible();

        // El dirigente lo ve en su lista, con la captura, y lo aprueba.
        await dir.goto('/dirigente');
        await dir.getByRole('link', { name: /Contactos por revisar \(1\)/ }).click();
        await dir.getByRole('link', { name: /Lima, Perú/ }).click();
        await expect(dir.getByText('Grupo Scout Lima 1')).toBeVisible();
        await expect(dir.locator('img[src*="/captura"]')).toHaveCount(1);
        await expect(dir.getByText(/15 puntos/).first()).toBeVisible();
        await dir.getByRole('button', { name: 'Aprobar' }).click();
        await expect(dir.getByRole('heading', { name: '¡Contacto aprobado!' })).toBeVisible();

        expect(puntosDe('4000003')).toBe(antes + 15);
        await ana.goto('/app/contactos/joti');
        await expect(ana.getByText('Aprobado').first()).toBeVisible();
    });

    test('JOTA se carga como libro de guardia, es local por el indicativo y suma a la patrulla', async ({ browser }) => {
        definirPuntajes();
        const { page: ana } = await sesionDe(browser, '4000004');
        const { page: dir } = await sesionDe(browser, '4000045');
        const antes = puntosPatrulla('4000004');

        await ana.goto('/app/contactos/jota/nuevo');
        await ana.getByLabel('Estación (indicativo)').fill('zp5zdm');
        await ana.getByLabel('RST emisión').fill('5-9');
        await ana.getByLabel('RST recepción').fill('5-4');
        await ana.getByLabel('Frecuencia (MHz)').fill('7,125');
        await ana.getByLabel('Modo de emisión').selectOption('LSB');
        await ana.getByLabel('Observaciones (opcional)').fill('Christian');
        await ana.getByLabel('Hubo emisión (QSL E)').check();
        await ana.getByRole('button', { name: 'Enviar contacto' }).click();
        await expect(ana.getByRole('heading', { name: '¡Contacto enviado!' })).toBeVisible();

        await dir.goto('/dirigente/contactos');
        await dir.getByRole('link', { name: /ZP5ZDM/ }).click();
        await expect(dir.getByText('7.125 MHz')).toBeVisible();
        await expect(dir.getByText('Local').first()).toBeVisible();
        await expect(dir.getByText(/10 puntos para la patrulla/)).toBeVisible();
        await dir.getByRole('button', { name: 'Aprobar' }).click();
        await expect(dir.getByRole('heading', { name: '¡Contacto aprobado!' })).toBeVisible();

        expect(puntosPatrulla('4000004')).toBe(antes + 10);
    });

    test('un contacto rechazado muestra el motivo y no se puede volver a enviar ni aprobar', async ({ browser }) => {
        definirPuntajes();
        const { page: ana } = await sesionDe(browser, '4000005');
        const { page: dir } = await sesionDe(browser, '4000045');

        await ana.goto('/app/contactos/joti/nuevo');
        await ana.getByLabel('Grupo scout').fill('Grupo Quito 2');
        await ana.getByLabel('Ciudad').fill('Quito');
        await ana.getByLabel('Unidad', { exact: true }).fill('Caminantes');
        await ana.getByLabel('Otro país').check();
        await ana.getByLabel('¿Cuál?').fill('Ecuador');
        await ana.locator('input[type=file]').setInputFiles({ name: 'chat.png', mimeType: 'image/png', buffer: FOTO });
        await ana.getByRole('button', { name: 'Enviar contacto' }).click();
        await expect(ana.getByRole('heading', { name: '¡Contacto enviado!' })).toBeVisible();

        await dir.goto('/dirigente/contactos');
        await dir.getByRole('link', { name: /Quito, Ecuador/ }).click();
        await dir.getByRole('button', { name: 'Rechazar' }).click();
        await expect(dir.getByLabel('Motivo del rechazo')).toBeVisible(); // esperar al formulario antes de volver a tocar «Rechazar»
        await dir.getByRole('button', { name: 'Rechazar' }).click(); // sin motivo
        await expect(dir.getByText('Para rechazar tenés que escribir el motivo.')).toBeVisible();
        await dir.getByLabel('Motivo del rechazo').fill('No se ve la conversación');
        await dir.getByRole('button', { name: 'Rechazar' }).click();
        await expect(dir.getByRole('heading', { name: 'Contacto rechazado' })).toBeVisible();

        await ana.goto('/app/contactos/joti');
        await expect(ana.getByText('Rechazado').first()).toBeVisible();
        await expect(ana.getByText('No se ve la conversación')).toBeVisible();
        await expect(ana.getByText('no se puede volver a enviar').first()).toBeVisible();
    });
});
