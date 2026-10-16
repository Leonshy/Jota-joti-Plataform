/*
 * Auditoría de pantallas a 360 px reales (para el QA completo).
 *
 * Por qué existe: redimensionar la ventana del navegador de escritorio NO llega a 360 px (el mínimo es
 * ~500 px), así que las capturas "a 360" salían a 500 px. Esto mide cada pantalla dentro de un <iframe>
 * de 360 px de ancho, que sí tiene su propio viewport.
 *
 * Uso: levantar `php artisan serve`, abrir http://127.0.0.1:8000/diseno en Chrome, pegar este archivo en
 * la consola de DevTools (o correrlo con evaluate_script). Devuelve solo las pantallas con problemas.
 *
 * Revisa: desborde horizontal, textos cortados con "…", elementos que se salen por la derecha,
 * objetivos táctiles menores a 44 px y textos menores a 14 px.
 */
(async () => {
  const urls = ['ingresar', 'ingresar?estado=pin', 'activar', 'activar?estado=pin', 'inicio', 'inicio?estado=vacio', 'misiones', 'mision', 'mision?estado=rechazada', 'mision?estado=patrulla', 'entregar', 'entregar?estado=error', 'actividades', 'actividades?estado=choque', 'escanear', 'escanear?estado=sin-camara', 'confirmar-pago', 'confirmar-pago?estado=saldo', 'pago-ok', 'asistencia-ok', 'ranking', 'ranking?estado=rover', 'historial', 'd-cola', 'd-revisar', 'd-revisar?estado=rechazar', 'd-qr', 'd-asistencia', 'd-cumplimientos', 'd-penalizaciones', 'd-ranking', 't-cobrar', 't-qr', 't-devolver', 't-devolver?estado=form', 't-historial'];
  await document.fonts.load('700 14px Poppins');
  const resultados = [];

  for (const u of urls) {
    const f = document.createElement('iframe');
    f.style.cssText = 'position:fixed;left:0;top:0;width:360px;height:780px;border:0;visibility:hidden';
    f.src = '/diseno/' + u;
    document.body.appendChild(f);
    await new Promise((r) => f.addEventListener('load', r, { once: true }));
    await f.contentDocument.fonts.ready; // medir con la tipografía ya cargada
    await new Promise((r) => setTimeout(r, 150));

    const d = f.contentDocument, w = f.contentWindow, ancho = w.innerWidth, problemas = [];
    if (d.documentElement.scrollWidth > ancho + 1) problemas.push('desborde horizontal: ' + d.documentElement.scrollWidth);

    d.querySelectorAll('*').forEach((el) => {
      if (w.getComputedStyle(el).textOverflow === 'ellipsis' && el.scrollWidth > el.clientWidth + 1 && el.textContent.trim()) problemas.push('texto cortado: "' + el.textContent.trim().slice(0, 30) + '"');
    });
    d.querySelectorAll('main *, header *, nav *').forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.width > 0 && r.right > ancho + 1 && !el.closest('.sr-only')) problemas.push('se sale: ' + el.tagName + '.' + String(el.className).slice(0, 40));
    });
    d.querySelectorAll('a[href], button, input, textarea').forEach((el) => {
      const r = el.getBoundingClientRect();
      if (!r.width || !r.height || el.closest('.sr-only') || el.type === 'hidden') return;
      if (el.tagName === 'A' && el.closest('p') && !el.className.includes('inline-flex')) return; // enlace dentro de un párrafo
      if (r.height < 44 || r.width < 44) problemas.push('táctil chico ' + Math.round(r.width) + 'x' + Math.round(r.height) + ': ' + (el.textContent.trim().slice(0, 22) || el.getAttribute('aria-label') || el.tagName));
    });
    let chico = 0;
    const tw = d.createTreeWalker(d.body, NodeFilter.SHOW_TEXT);
    while (tw.nextNode()) {
      const p = tw.currentNode.parentElement;
      if (tw.currentNode.textContent.trim() && p && !p.closest('.sr-only, svg, script, style') && parseFloat(w.getComputedStyle(p).fontSize) < 14) chico++;
    }
    if (chico) problemas.push('texto < 14px: ' + chico);

    f.remove();
    if (problemas.length) resultados.push({ url: u, problemas: [...new Set(problemas)].slice(0, 6) });
  }

  return { revisadas: urls.length, conProblemas: resultados.length, detalle: resultados };
})();
