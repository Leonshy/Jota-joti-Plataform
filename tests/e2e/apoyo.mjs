import { expect } from '@playwright/test';
import { tinker } from './entorno.mjs';

export const PIN = '1234';

// Foto de 1×1 píxel (PNG válido): alcanza para recorrer el flujo de subida, re-codificación y revisión.
export const FOTO = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'base64');

/** Entra con CI + PIN y espera a llegar a la pantalla de su rol. */
export async function entrar(page, ci, pin = PIN, destino = /\/(app|dirigente|tienda|admin)/) {
    await page.goto('/ingresar');
    await page.getByLabel('Tu número de CI').fill(ci);
    await page.getByLabel('Tu PIN').fill(pin);
    await page.getByRole('button', { name: 'Ingresar' }).click();
    await expect(page).toHaveURL(destino);
}

export async function salir(page) {
    await page.getByRole('button', { name: 'Cerrar sesión' }).click();
    await expect(page).toHaveURL(/\/ingresar/);
}

/** Una sesión nueva e independiente (otro «celular») ya ingresada. */
export async function sesionDe(browser, ci, pin = PIN) {
    const contexto = await browser.newContext({ viewport: { width: 360, height: 740 }, locale: 'es-PY', timezoneId: 'America/Asuncion', hasTouch: true, isMobile: true });
    const page = await contexto.newPage();
    await entrar(page, ci, pin);

    return { page, contexto };
}

/** Dirección que codifica el QR de asistencia vigente de la última sesión abierta (lo que leería el celular). */
export function urlQrAsistencia() {
    return tinker('echo app(App\\Domain\\Actividades\\QrAsistencia::class)->url(App\\Models\\SesionAsistencia::whereNull("cerrada_at")->latest("id")->first());');
}

/** Dirección que codifica el QR del último cobro generado. */
export function urlQrCobro() {
    return tinker('echo route("cobro.ver", App\\Models\\Cobro::latest("id")->first()->token);');
}

export function saldo(ci) {
    return JSON.parse(tinker(`$u = App\\Models\\User::where("ci", "${ci}")->first(); echo json_encode(["puntos" => $u->puntos, "jlis" => $u->jlis]);`));
}
