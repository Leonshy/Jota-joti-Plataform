// Sin axios ni bootstrap por defecto: estas pantallas no hacen llamadas HTTP desde JS (Livewire trae las suyas).

/*
 * Interacciones mínimas del sistema de diseño (sin dependencias). Todo respeta "reducir movimiento".
 * Livewire trae su propio Alpine; esto es solo para los momentos con animación propia.
 */

/**
 * Achica una foto ANTES de subirla: máximo 1600 px de lado y JPEG de calidad 0,82, que es lo mismo en lo que el servidor la
 * convierte igual. Una foto de celular pasa de varios MB a unos cientos de KB: sube rápido con datos móviles, no choca con el
 * límite de subida de PHP del servidor (que en Plesk suele ser de 2 MB) y no le exige memoria de más a la cola de imágenes.
 * Además la foto sale sin EXIF (ubicación GPS). Si el navegador no sabe leer el formato (p. ej. HEIC), se manda tal cual y valida
 * el servidor. Se usa desde las pantallas con `x-on:change` y `$wire.upload(...)`.
 */
window.jotaFotoLiviana = async function (archivo, lado = 1600, calidad = 0.82) {
    if (! archivo || ! archivo.type?.startsWith('image/')) return archivo;

    try {
        const imagen = await createImageBitmap(archivo, { imageOrientation: 'from-image' });
        const escala = Math.min(1, lado / Math.max(imagen.width, imagen.height));
        const lienzo = document.createElement('canvas');
        lienzo.width = Math.max(1, Math.round(imagen.width * escala));
        lienzo.height = Math.max(1, Math.round(imagen.height * escala));

        const contexto = lienzo.getContext('2d');
        contexto.fillStyle = '#fff'; // las capturas PNG con transparencia no quedan negras al pasar a JPEG
        contexto.fillRect(0, 0, lienzo.width, lienzo.height);
        contexto.drawImage(imagen, 0, 0, lienzo.width, lienzo.height);
        imagen.close?.();

        const blob = await new Promise((resolver) => lienzo.toBlob(resolver, 'image/jpeg', calidad));

        // Si no mejoró (ya era chica), se sube la original.
        if (! blob || (escala === 1 && blob.size >= archivo.size)) return archivo;

        return new File([blob], (archivo.name.replace(/\.[^.]+$/, '') || 'foto') + '.jpg', { type: 'image/jpeg' });
    } catch {
        return archivo;
    }
};

const reducirMovimiento = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const salida = (t) => 1 - Math.pow(1 - t, 4); // exponencial, como --ease-salida
const formato = new Intl.NumberFormat('es-PY');

/** Cuenta de un número a otro (saldos que se acreditan o se descuentan). */
function contar(el) {
    const desde = Number(el.dataset.desde ?? 0);
    const hasta = Number(el.dataset.hasta ?? 0);
    const duracion = Number(el.dataset.duracion ?? 550);

    if (reducirMovimiento() || desde === hasta) {
        el.textContent = formato.format(hasta);
        return;
    }

    const inicio = performance.now();
    const paso = (ahora) => {
        const t = Math.min((ahora - inicio) / duracion, 1);
        el.textContent = formato.format(Math.round(desde + (hasta - desde) * salida(t)));
        if (t < 1) requestAnimationFrame(paso);
    };
    el.textContent = formato.format(desde);
    setTimeout(() => requestAnimationFrame(paso), Number(el.dataset.espera ?? 200));
}

/**
 * Teclado numérico de la tienda: arma el monto en pantalla. El valor vive en el campo oculto (o, sin campo,
 * en el propio teclado) y el escuchador es global, así sigue andando cuando Livewire vuelve a dibujar la página.
 */
function teclado() {
    document.addEventListener('click', (e) => {
        const boton = e.target.closest('[data-teclado] [data-tecla]');
        if (! boton) return;

        const raiz = boton.closest('[data-teclado]');
        const campos = document.querySelectorAll('[data-monto-valor]');
        let valor = campos[0] ? campos[0].value : (raiz.dataset.valor ?? '');
        const tecla = boton.dataset.tecla;

        if (tecla === 'limpiar') valor = '';
        else if (tecla === 'borrar') valor = valor.slice(0, -1);
        else if (valor.length < 7) valor = (valor + tecla).replace(/^0+/, '');

        raiz.dataset.valor = valor;
        document.querySelectorAll('[data-monto]').forEach((s) => (s.textContent = formato.format(Number(valor || 0))));
        document.querySelectorAll('[data-monto-unidad]').forEach((u) => (u.textContent = Number(valor || 0) === 1 ? u.dataset.singular : u.dataset.plural));
        campos.forEach((i) => {
            i.value = valor;
            i.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
}

/** Cuenta regresiva mm:ss. */
function cuentaRegresiva(el) {
    let restante = Number(el.dataset.cuentaRegresiva);
    const pintar = () => {
        const m = Math.floor(restante / 60);
        const s = String(restante % 60).padStart(2, '0');
        el.textContent = `${m}:${s}`;
        el.classList.toggle('pulso-aviso', restante <= 10 && ! reducirMovimiento());
    };
    pintar();
    const id = setInterval(() => {
        restante = Math.max(0, restante - 1);
        pintar();
        if (restante === 0) clearInterval(id);
    }, 1000);
}

/** QR que rota: el código viejo sale y el nuevo entra con un fundido corto. (Demostración del diseño.) */
function qrRota(el) {
    const ciclo = Number(el.dataset.ciclo ?? 60);
    const contenido = el.querySelector('[data-qr-contenido]');
    const copia = contenido.innerHTML;

    setInterval(() => {
        // Con "reducir movimiento" el código se renueva igual (es seguridad, no adorno): solo se omite la transición.
        if (reducirMovimiento()) {
            contenido.innerHTML = copia;
            return;
        }
        contenido.classList.add('animate-qr-sale');
        setTimeout(() => {
            contenido.classList.remove('animate-qr-sale');
            contenido.innerHTML = copia; // en la app real llega el SVG nuevo desde el servidor
            contenido.classList.add('animate-qr-entra');
            setTimeout(() => contenido.classList.remove('animate-qr-entra'), 300);
        }, 160);
    }, ciclo * 1000);
}

/** Repite una animación (para la guía de estilo): reinicia el elemento sin recargar la página. */
function repetir(boton) {
    boton.addEventListener('click', () => {
        const destino = document.querySelector(boton.dataset.repetir);
        if (! destino) return;
        // Solo se reinicia el nombre de la animación: así no se pierden los retrasos escalonados en línea.
        destino.querySelectorAll('*').forEach((n) => { n.style.animationName = 'none'; });
        void destino.offsetWidth;
        destino.querySelectorAll('*').forEach((n) => { n.style.animationName = ''; });
        destino.querySelectorAll('[data-contar]').forEach(contar);
    });
}

/**
 * Activa los elementos con comportamiento propio (cuentas, saldos que cuentan, teclado). Se corre al cargar y cada vez
 * que Livewire agrega algo nuevo a la página; cada elemento se marca para no arrancarlo dos veces.
 */
function activar() {
    document.querySelectorAll('[data-repetir]:not([data-listo])').forEach((el) => { el.dataset.listo = '1'; repetir(el); });
    document.querySelectorAll('[data-contar]:not([data-listo])').forEach((el) => { el.dataset.listo = '1'; contar(el); });
    document.querySelectorAll('[data-cuenta-regresiva]:not([data-listo])').forEach((el) => { el.dataset.listo = '1'; cuentaRegresiva(el); });
    document.querySelectorAll('[data-qr-rota]:not([data-listo])').forEach((el) => { el.dataset.listo = '1'; qrRota(el); });

    // El escáner (y jsQR) solo se descargan en la pantalla de Escanear.
    document.querySelectorAll('[data-escaner]:not([data-listo])').forEach((el) => {
        el.dataset.listo = '1';
        import('./escaner').then((m) => m.iniciarEscaner(el));
    });
}

document.addEventListener('DOMContentLoaded', () => {
    teclado();
    activar();

    let pendiente = false;
    new MutationObserver(() => {
        if (pendiente) return;
        pendiente = true;
        requestAnimationFrame(() => { pendiente = false; activar(); });
    }).observe(document.body, { childList: true, subtree: true });
});
