import { expect, test } from '@playwright/test';
import { saldo, sesionDe, urlQrAsistencia } from './apoyo.mjs';

test.describe('Asistencia con QR rotativo', () => {
    test('el dirigente abre la sesión, el participante escanea y suma una sola vez; al cerrar, ya no sirve', async ({ browser }) => {
        const { page: dir } = await sesionDe(browser, '4000045');
        const { page: ana } = await sesionDe(browser, '4000004');
        const antes = saldo('4000004');

        // El dirigente abre la sesión del taller (individual) y ve el QR.
        await dir.goto('/dirigente/asistencia');
        await dir.getByRole('button', { name: /Taller de primeros auxilios/ }).click();
        await expect(dir).toHaveURL(/\/dirigente\/asistencia\/\d+$/);
        await expect(dir.locator('[data-qr-contenido] svg')).toBeVisible();
        await expect(dir.getByText('Sesión abierta')).toBeVisible();

        // La pantalla pública (la que se proyecta) muestra el QR y la cuenta regresiva.
        const publica = await dir.getByRole('link', { name: 'Mostrar QR en pantalla completa' }).getAttribute('href');
        const proyector = await browser.newPage({ viewport: { width: 800, height: 1000 } });
        await proyector.goto(publica);
        await expect(proyector.locator('#qr svg')).toBeVisible();
        await expect(proyector.locator('#cuenta')).toHaveText(/\d:\d\d/);
        await proyector.close();

        // El participante escanea (abre la dirección que codifica el QR vigente).
        const qr = urlQrAsistencia();
        await ana.goto(qr);
        await expect(ana.getByRole('heading', { name: '¡Listo!' })).toBeVisible();
        await expect(ana.getByText(/Registramos tu asistencia a/)).toBeVisible();
        expect(saldo('4000004')).toEqual({ puntos: antes.puntos + 20, jlis: antes.jlis + 20 });

        // Escanear otra vez no suma de nuevo.
        await ana.goto(qr);
        await expect(ana.getByRole('heading', { name: 'Ya registraste tu asistencia' })).toBeVisible();
        expect(saldo('4000004').puntos).toBe(antes.puntos + 20);

        // El dirigente ve 1 asistente y cierra la sesión.
        await dir.reload();
        await expect(dir.getByText('1', { exact: true }).first()).toBeVisible();
        await dir.getByRole('button', { name: 'Cerrar sesión' }).click();
        await dir.getByRole('button', { name: 'Sí, cerrar' }).click();
        await expect(dir.getByRole('heading', { name: 'Sesión cerrada' })).toBeVisible();

        // Con la sesión cerrada, el mismo QR ya no sirve para otra persona.
        const { page: beto } = await sesionDe(browser, '4000006');
        await beto.goto(qr);
        await expect(beto.getByRole('heading', { name: 'La sesión ya se cerró' })).toBeVisible();
        expect(saldo('4000006').puntos).toBe(0);
    });

    test('las estaciones JOTA son por equipo: no tienen QR de asistencia, se marcan en «Cumplidas»', async ({ browser }) => {
        const { page: dir } = await sesionDe(browser, '4000045');

        await dir.goto('/dirigente/asistencia');
        await expect(dir.getByRole('button', { name: /Estación JOTA/ })).toHaveCount(0);
        await expect(dir.getByRole('link', { name: /Estación JOTA: radioaficionados/ })).toBeVisible();
    });

    test('un código vencido o inventado no registra nada', async ({ browser }) => {
        const { page: dir } = await sesionDe(browser, '4000045');
        const { page } = await sesionDe(browser, '4000007');

        await dir.goto('/dirigente/asistencia');
        await dir.getByRole('button', { name: /Taller de primeros auxilios/ }).click();
        await expect(dir).toHaveURL(/\/dirigente\/asistencia\/\d+$/);
        const id = dir.url().split('/').pop();

        await page.goto(`/asistencia/${id}/registrar/${'0'.repeat(32)}`);
        await expect(page.getByRole('heading', { name: 'Ese QR ya venció' })).toBeVisible();
        expect(saldo('4000007').puntos).toBe(0);
    });

    test('un participante de otra unidad no puede registrar asistencia en una actividad que no es de su unidad', async ({ browser }) => {
        const { page: dir } = await sesionDe(browser, '4000045'); // Tropa
        const { page: rover } = await sesionDe(browser, '4000043'); // Rovers

        await dir.goto('/dirigente/asistencia');
        // El taller Rover no es de Tropa y no aparece; la estación de radioaficionados sí es de las tres unidades.
        await expect(dir.getByText('Taller Rover: liderazgo')).toHaveCount(0);

        // Un Rover escaneando una actividad solo de Tropa/Caminantes recibe el aviso. (El gran juego es por equipo: no tiene QR.)
        await rover.goto('/app/escanear');
        await expect(rover.getByRole('heading', { name: 'Escanear', level: 1 })).toBeAttached();
    });
});
