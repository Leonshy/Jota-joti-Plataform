import jsQR from 'jsqr';

/*
 * Escáner de QR con la cámara. Usa el detector nativo del navegador si existe y, si no, jsQR.
 * Solo sigue enlaces de ESTE mismo sitio y con la forma de un QR de asistencia o de cobro:
 * cualquier otro código se descarta con un aviso (nunca se navega a un sitio ajeno).
 */
const RUTAS_VALIDAS = [/^\/asistencia\/\d+\/registrar\/[A-Za-z0-9_-]+$/, /^\/cobros\/[A-Za-z0-9_-]+$/];

export function destinoDelQr(texto, origen = window.location.origin) {
    try {
        const url = new URL(texto, origen);

        if (url.origin !== origen) return null;

        return RUTAS_VALIDAS.some((r) => r.test(url.pathname)) ? url.pathname : null;
    } catch {
        return null;
    }
}

/**
 * Dónde se está abriendo la página, para dar las instrucciones exactas de cada caso.
 * Devuelve: ios-safari | ios-chrome | ios-otro | android-chrome | android-samsung | android-firefox |
 * android-otro | escritorio | integrado (navegador dentro de otra app, que no puede pedir la cámara bien).
 */
export function clasificarNavegador(ua = navigator.userAgent, plataforma = navigator.platform, toques = navigator.maxTouchPoints) {
    // Los iPad nuevos se presentan como Mac: se distinguen porque tienen pantalla táctil.
    const ios = /iPhone|iPad|iPod/.test(ua) || (plataforma === 'MacIntel' && toques > 1);
    const android = /Android/.test(ua);

    if (/FBAN|FBAV|Instagram|Line\/|MicroMessenger|TikTok|Twitter/.test(ua)) return 'integrado';

    if (ios) {
        if (/CriOS/.test(ua)) return 'ios-chrome';
        if (/FxiOS|EdgiOS|OPiOS/.test(ua)) return 'ios-otro';

        return 'ios-safari';
    }

    if (android) {
        if (/SamsungBrowser/.test(ua)) return 'android-samsung';
        if (/Firefox/.test(ua)) return 'android-firefox';
        if (/EdgA|OPR|DuckDuckGo/.test(ua)) return 'android-otro';

        return 'android-chrome';
    }

    return 'escritorio';
}

/** Por qué no hay cámara: inseguro (sin HTTPS) | denegado (permiso) | sin-camara | ocupada | otro. */
export function motivoSinCamara(error, seguro = window.isSecureContext) {
    if (!seguro || !navigator.mediaDevices?.getUserMedia) return 'inseguro';

    switch (error?.name) {
        case 'NotAllowedError':
        case 'SecurityError':
            return 'denegado';
        case 'NotFoundError':
        case 'OverconstrainedError':
            return 'sin-camara';
        case 'NotReadableError':
        case 'AbortError':
            return 'ocupada';
        default:
            return 'otro';
    }
}

export async function iniciarEscaner(el) {
    const video = el.querySelector('[data-escaner-video]');
    const camara = el.querySelector('[data-escaner-camara]');
    const sinCamara = el.querySelector('[data-escaner-sin-camara]');
    const aviso = el.querySelector('[data-escaner-aviso]');
    let flujo = null;
    let activo = true;

    // Muestra solo el motivo y, si es un permiso bloqueado, las instrucciones del navegador de esta persona.
    const mostrarSinCamara = (error) => {
        const motivo = motivoSinCamara(error);
        const navegador = clasificarNavegador();

        camara.hidden = true;
        sinCamara.hidden = false;
        sinCamara.querySelectorAll('[data-motivo]').forEach((n) => (n.hidden = n.dataset.motivo !== motivo));

        // Si la página está dentro de otra app, lo primero es salir de ella; si no, lo propio del navegador.
        const clave = navegador === 'integrado' && motivo !== 'inseguro' ? 'integrado' : navegador;
        sinCamara.querySelectorAll('[data-navegador]').forEach((n) => (n.hidden = n.dataset.navegador !== clave));
    };

    if (!navigator.mediaDevices?.getUserMedia) {
        mostrarSinCamara();
        return;
    }

    try {
        flujo = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
    } catch (error) {
        mostrarSinCamara(error);
        return;
    }

    video.srcObject = flujo;
    await video.play().catch(() => {});

    const detector = 'BarcodeDetector' in window ? new BarcodeDetector({ formats: ['qr_code'] }) : null;
    const lienzo = document.createElement('canvas');
    const contexto = lienzo.getContext('2d', { willReadFrequently: true });
    let ultimoAviso = 0;

    const parar = () => {
        activo = false;
        flujo?.getTracks().forEach((t) => t.stop());
    };

    const leer = async () => {
        if (video.readyState < 2) return null;

        if (detector) {
            const codigos = await detector.detect(video).catch(() => []);

            return codigos[0]?.rawValue ?? null;
        }

        const lado = Math.min(video.videoWidth, 640);
        const alto = Math.round((video.videoHeight / video.videoWidth) * lado);
        lienzo.width = lado;
        lienzo.height = alto;
        contexto.drawImage(video, 0, 0, lado, alto);

        return jsQR(contexto.getImageData(0, 0, lado, alto).data, lado, alto)?.data ?? null;
    };

    const ciclo = async () => {
        if (!activo) return;

        const texto = await leer();

        if (texto) {
            const destino = destinoDelQr(texto);

            if (destino) {
                parar();
                aviso.textContent = aviso.dataset.leyendo;
                window.location.href = destino;

                return;
            }

            if (Date.now() - ultimoAviso > 2500) {
                ultimoAviso = Date.now();
                aviso.textContent = aviso.dataset.noReconocido;
                aviso.classList.add('text-error-texto');
            }
        }

        setTimeout(() => requestAnimationFrame(ciclo), 120);
    };

    window.addEventListener('pagehide', parar);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) parar();
    });

    ciclo();
}
