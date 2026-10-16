/*
 * Prueba de carga de la plataforma (Fase 10): 300 participantes + 3 dirigentes + 1 tienda al mismo tiempo, con la mezcla
 * del evento: ingreso, sondeos de Inicio / Ranking / Agenda cada 30 s, entregas (con foto), escaneos de asistencia,
 * reservas, pagos con QR (algunos con doble toque) y revisión de entregas. Habla HTTP real contra un servidor y una base
 * PROPIOS (jotajoti_carga). Al terminar compara lo que hizo cada usuario con lo que quedó en la base y corre jota:reconciliar.
 *
 * Uso:  node tests/carga/carga.mjs [--usuarios 300] [--segundos 240] [--workers 24] [--sondeo 30] [--salida archivo.md]
 * Nota: usa el servidor incorporado de PHP (un proceso por petición, sin caché de código): es MÁS lento que el Apache/PHP-FPM
 * de producción, así que los tiempos de acá son una cota pesimista, no una promesa.
 */
import { spawn, spawnSync } from 'node:child_process';
import { createHmac } from 'node:crypto';
import { mkdirSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { performance } from 'node:perf_hooks';

const arg = (n, d) => { const i = process.argv.indexOf(`--${n}`); return i > -1 ? Number(process.argv[i + 1]) : d; };
const USUARIOS = arg('usuarios', 300);
const SEGUNDOS = arg('segundos', 240);
const WORKERS = arg('workers', 24);
const SONDEO = arg('sondeo', 30); // segundos entre sondeos (en la app real: 30 en Inicio, Ranking y Agenda)
const SALIDA = process.argv.includes('--salida') ? process.argv[process.argv.indexOf('--salida') + 1] : 'docs/qa/informe-carga.md';
const PUERTO = 8124;
const BASE = `http://127.0.0.1:${PUERTO}`;
const ENTORNO = {
    APP_ENV: 'local', APP_DEBUG: 'false', APP_URL: BASE, DB_DATABASE: 'jotajoti_carga', QUEUE_CONNECTION: 'database',
    CACHE_STORE: 'database', SESSION_DRIVER: 'database', LOG_CHANNEL: 'single', LOG_LEVEL: 'warning', SEED_PIN: '1234',
};

const php = (args) => spawnSync('php', args, { env: { ...process.env, ...ENTORNO }, encoding: 'utf8', maxBuffer: 1 << 26 });
const tinker = (codigo) => { const r = php(['artisan', 'tinker', '--execute', codigo]); const l = (r.stdout || '').trim().split('\n').filter(Boolean); return l[l.length - 1] ?? ''; };
const dormir = (ms) => new Promise((r) => setTimeout(r, ms));
const azar = (a, b) => a + Math.random() * (b - a);
const t0 = performance.now();
const ahora = () => (performance.now() - t0) / 1000;

// ── Métricas ────────────────────────────────────────────────────────────────
const metricas = new Map();
const contadores = { scanOk: 0, scanYa: 0, scanOtro: 0, pagoOk: 0, pagoYa: 0, pagoOtro: 0, entregaOk: 0, entregaFoto: 0, reservaOk: 0, reservaOtro: 0, aprobadas: 0, ingresos: 0, ingresoFallido: 0 };
function registrar(etiqueta, ms, estado, extra = {}) {
    const m = metricas.get(etiqueta) ?? { n: 0, lat: [], err: 0, limitado: 0, bytes: 0 };
    m.n++; m.lat.push(ms); m.bytes += extra.bytes ?? 0;
    if (estado === 429) m.limitado++;
    else if (estado === 0 || estado >= 500 || estado === 419) m.err++;
    metricas.set(etiqueta, m);
}
const pct = (arr, p) => { const s = [...arr].sort((a, b) => a - b); return s.length ? s[Math.min(s.length - 1, Math.floor((p / 100) * s.length))] : 0; };

// ── Cliente HTTP con cookies ────────────────────────────────────────────────
class Cliente {
    constructor() { this.cookies = new Map(); }
    guardar(res) { for (const c of res.headers.getSetCookie?.() ?? []) { const [par] = c.split(';'); const i = par.indexOf('='); this.cookies.set(par.slice(0, i), par.slice(i + 1)); } }
    cabecera() { return [...this.cookies].map(([k, v]) => `${k}=${v}`).join('; '); }
    async pedir(etiqueta, url, opciones = {}) {
        const inicio = performance.now();
        let estado = 0; let texto = ''; let res;
        try {
            res = await fetch(url.startsWith('http') ? url : BASE + url, { redirect: 'manual', ...opciones, headers: { Cookie: this.cabecera(), ...opciones.headers } });
            this.guardar(res); estado = res.status; texto = await res.text();
        } catch (e) { estado = 0; texto = String(e); }
        registrar(etiqueta, performance.now() - inicio, estado, { bytes: texto.length });
        return { estado, texto, res };
    }
    get(etiqueta, url) { return this.pedir(etiqueta, url, { headers: { Accept: 'text/html' } }); }
}

const desescapar = (s) => s.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
function leerLivewire(html) {
    const snap = html.match(/wire:snapshot="([^"]*)"/);
    const csrf = html.match(/data-csrf="([^"]+)"/)?.[1];
    const uri = html.match(/data-update-uri="([^"]+)"/)?.[1];
    return snap && csrf && uri ? { snapshot: desescapar(snap[1]), csrf, uri } : null;
}

/** Una llamada de Livewire (lo que hace el navegador al tocar un botón o en cada sondeo). Devuelve el estado nuevo. */
async function lw(cli, etiqueta, est, { updates = {}, calls = [] } = {}) {
    const cuerpo = JSON.stringify({ _token: est.csrf, components: [{ snapshot: est.snapshot, updates, calls: calls.map((c) => ({ method: c[0], params: c[1] ?? [], metadata: {} })) }] });
    const r = await cli.pedir(etiqueta, est.uri, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Livewire': '1', Accept: 'application/json' }, body: cuerpo });
    let json = null; try { json = JSON.parse(r.texto); } catch { /* respuesta no JSON */ }
    const comp = json?.components?.[0];
    if (comp?.snapshot) est.snapshot = comp.snapshot;
    return { estado: r.estado, comp, efectos: comp?.effects ?? {}, html: comp?.effects?.html ?? '', texto: r.texto };
}

// ── Preparación ─────────────────────────────────────────────────────────────
function preparar() {
    console.log('· Preparando la base de carga y los datos…');
    php(['-r', 'echo 1;']);
    spawnSync('mysql', ['-uroot', '-e', 'DROP DATABASE IF EXISTS jotajoti_carga; CREATE DATABASE jotajoti_carga CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;']);
    php(['artisan', 'migrate:fresh', '--seed', '--force']);
    // Foto realista (~2 MP con ruido, JPEG de cientos de KB) para las entregas con imagen.
    php(['-r', '$im=imagecreatetruecolor(2000,1500);for($y=0;$y<1500;$y+=3)for($x=0;$x<2000;$x+=3){imagefilledrectangle($im,$x,$y,$x+2,$y+2,imagecolorallocate($im,($x*7+$y)%256,($y*5+$x*3)%256,mt_rand(0,255)));}imagejpeg($im,"/tmp/foto-carga.jpg",88);']);
    console.log('  foto de prueba:', Math.round(statSync('/tmp/foto-carga.jpg').size / 1024), 'KB');

    tinker(`
        $hash = Hash::make('1234');
        $pats = [1 => App\\Models\\Patrulla::where('unidad_id', 1)->pluck('id')->all(), 2 => App\\Models\\Patrulla::where('unidad_id', 2)->pluck('id')->all()];
        $tercio = (int) floor(${USUARIOS} * 0.4);
        $filas = [];
        for ($i = 1; $i <= ${USUARIOS}; $i++) {
            $u = $i <= $tercio ? 1 : ($i <= 2 * $tercio ? 2 : 3);
            $filas[] = ['ci' => (string) (6000000 + $i), 'name' => 'Carga '.$i, 'rol' => 'beneficiario', 'unidad_id' => $u, 'grupo_id' => 1,
                'patrulla_id' => $u === 3 ? null : $pats[$u][$i % count($pats[$u])], 'pin_hash' => $hash, 'activado_at' => now(), 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($filas, 100) as $lote) DB::table('users')->insert($lote);
        foreach (App\\Models\\User::where('ci', '>=', '6000001')->get() as $u) app(App\\Domain\\Ledger\\Jlis::class)->acreditar($u, 100, null, 'carga');
        App\\Models\\Mision::whereIn('id', [1, 3, 4])->update(['lanza_at' => now()->subHour(), 'vence_at' => null]);
        App\\Models\\Turno::whereKey(3)->update(['inicio' => now()->addHours(5), 'fin' => now()->addHours(6)]);
        echo 'listo';
    `);
    const sesion = JSON.parse(tinker(`
        $d = App\\Models\\User::where('ci', '4000045')->first();
        $s = app(App\\Domain\\Actividades\\AbrirSesionAsistencia::class)($d, App\\Models\\Actividad::find(1));
        echo json_encode(['id' => $s->id, 'secreto' => $s->secreto]);
    `));
    return sesion;
}

// Asíncrono: no frena a los demás usuarios mientras se generan los cobros.
const tinkerAsinc = (codigo) => new Promise((ok) => {
    const p = spawn('php', ['artisan', 'tinker', '--execute', codigo], { env: { ...process.env, ...ENTORNO }, stdio: ['ignore', 'pipe', 'ignore'] });
    let salida = '';
    p.stdout.on('data', (d) => (salida += d));
    p.on('close', () => ok(salida.trim().split('\n').filter(Boolean).pop() ?? ''));
});

const crearCobros = async (n) => JSON.parse(await tinkerAsinc(`
    $t = App\\Models\\User::where('ci', '4000048')->first(); $tokens = [];
    for ($i = 0; $i < ${n}; $i++) $tokens[] = app(App\\Domain\\Tienda\\CrearCobro::class)($t, 5)->token;
    echo json_encode($tokens);
`));

// ── Comportamiento de cada tipo de usuario ──────────────────────────────────
const compartido = { sesion: null, lotes: [] };
const tokenQr = () => { const v = Math.floor(Date.now() / 1000 / 60); return createHmac('sha256', compartido.sesion.secreto).update(`${compartido.sesion.id}:${v}`).digest('hex').slice(0, 32); };
let fin = Infinity;

async function ingresar(cli, ci) {
    const pag = await cli.get('ingresar (página)', '/ingresar');
    const est = leerLivewire(pag.texto);
    if (!est) { contadores.ingresoFallido++; return false; }
    const r = await lw(cli, 'ingresar (Livewire)', est, { updates: { ci, pin: '1234' }, calls: [['ingresar']] });
    if (r.efectos.redirect) { contadores.ingresos++; return true; }
    contadores.ingresoFallido++;
    return false;
}

async function participante(i) {
    const ci = String(6000000 + i);
    const cli = new Cliente();
    await dormir(azar(0, 20_000)); // las llegadas se reparten en 20 s
    if (!(await ingresar(cli, ci))) return;

    // Pantalla en la que se queda (sondea cada 30 s con wire:poll.30s).
    const pantalla = [['/app', 'inicio'], ['/app/ranking', 'ranking'], ['/app/actividades', 'agenda'], ['/app/misiones', 'misiones']][Math.floor(Math.random() * 4)];
    let pag = await cli.get(`página ${pantalla[1]}`, pantalla[0]);
    let est = leerLivewire(pag.texto);

    // Acciones de esta persona, en momentos al azar.
    const acciones = [];
    if (Math.random() < 0.7) acciones.push({ t: azar(30, SEGUNDOS - 40), f: () => escanear(cli) });
    if (Math.random() < 0.3) acciones.push({ t: Math.random() < 0.5 ? azar(60, 130) : azar(180, SEGUNDOS - 20), f: () => pagar(cli) });
    if (Math.random() < 0.2) acciones.push({ t: azar(30, SEGUNDOS - 60), f: () => entregar(cli, Math.random() < 0.4) });
    if (Math.random() < 0.12) acciones.push({ t: azar(30, SEGUNDOS - 30), f: () => reservar(cli) });
    acciones.sort((a, b) => a.t - b.t);

    let proximoSondeo = ahora() + azar(1, SONDEO);
    while (ahora() < fin) {
        if (acciones.length && ahora() >= acciones[0].t) { await acciones.shift().f(); continue; }
        if (est && ahora() >= proximoSondeo) {
            await lw(cli, `sondeo ${pantalla[1]}`, est);
            proximoSondeo = ahora() + SONDEO;
        }
        await dormir(1000);
    }
}

async function escanear(cli) {
    const r = await cli.get('escanear QR (asistencia)', `/asistencia/${compartido.sesion.id}/registrar/${tokenQr()}`);
    if (r.estado === 200 && r.texto.includes('¡Listo!')) contadores.scanOk++;
    else if (r.estado === 200 && r.texto.includes('Ya registraste')) contadores.scanYa++;
    else contadores.scanOtro++;
}

async function pagar(cli) {
    // Cada persona toma un cobro del lote más nuevo que todavía no venció (valen 2 minutos).
    const lote = [...compartido.lotes].reverse().find((l) => ahora() - l.creado < 100 && l.siguiente < l.tokens.length);
    const token = lote?.tokens[lote.siguiente++];
    if (!token) return;
    const ver = await cli.get('pago: ver cobro', `/cobros/${token}`);
    const csrf = ver.texto.match(/name="_token" value="([^"]+)"/)?.[1];
    if (!csrf) { contadores.pagoOtro++; return; }
    const enviar = () => cli.pedir('pago: pagar', `/cobros/${token}/pagar`, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'text/html' }, body: `_token=${encodeURIComponent(csrf)}` });
    // 6 % de las personas tocan «Pagar» dos veces seguidas.
    const resultados = Math.random() < 0.06 ? await Promise.all([enviar(), enviar()]) : [await enviar()];
    for (const r of resultados) {
        if (r.texto.includes('¡Pago listo!')) contadores.pagoOk++;
        else if (r.texto.includes('ya fue pagado')) contadores.pagoYa++;
        else contadores.pagoOtro++;
    }
}

async function entregar(cli, conFoto) {
    const pag = await cli.get('entrega: abrir', '/app/misiones/1/entregar');
    const est = leerLivewire(pag.texto);
    if (!est) return;

    if (conFoto) {
        const buf = readFileSync('/tmp/foto-carga.jpg');
        const ini = await lw(cli, 'entrega: foto (iniciar)', est, { calls: [['_startUpload', ['nueva', [{ name: 'foto.jpg', size: buf.length, type: 'image/jpeg' }], false]]] });
        const url = ini.efectos.dispatches?.find((d) => d.name === 'upload:generatedSignedUrl')?.params?.url;
        if (!url) return;
        const form = new FormData();
        form.append('files[]', new Blob([buf], { type: 'image/jpeg' }), 'foto.jpg');
        const sub = await cli.pedir('entrega: foto (subir archivo)', url, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': est.csrf }, body: form });
        let paths; try { paths = JSON.parse(sub.texto).paths; } catch { return; }
        await lw(cli, 'entrega: foto (terminar)', est, { calls: [['_finishUpload', ['nueva', paths, false, false]]] });
    }

    const r = await lw(cli, 'entrega: enviar', est, { updates: { texto: 'Entrega de la prueba de carga.' }, calls: [['enviar']] });
    if (r.comp?.snapshot?.includes('"enviada":true')) { contadores.entregaOk++; if (conFoto) contadores.entregaFoto++; }
}

async function reservar(cli) {
    const pag = await cli.get('agenda: abrir', '/app/actividades');
    const est = leerLivewire(pag.texto);
    if (!est) return;
    const r = await lw(cli, 'agenda: reservar', est, { calls: [['reservar', [3]]] });
    if (r.html.includes('¡Reservado!') || r.texto.includes('Reservado!')) contadores.reservaOk++; else contadores.reservaOtro++;
}

async function dirigente(ci, intervalo) {
    const cli = new Cliente();
    await dormir(azar(0, 3000));
    if (!(await ingresar(cli, ci))) return;

    while (ahora() < fin) {
        const cola = await cli.get('dirigente: cola', '/dirigente');
        const id = cola.texto.match(/\/dirigente\/entregas\/(\d+)/)?.[1];
        if (id) {
            const pag = await cli.get('dirigente: abrir entrega', `/dirigente/entregas/${id}`);
            const est = leerLivewire(pag.texto);
            if (est) {
                const r = await lw(cli, 'dirigente: aprobar', est, { calls: [['aprobar']] });
                if (r.comp?.snapshot?.includes('"paso":"aprobada"')) contadores.aprobadas++;
            }
        }
        await dormir(intervalo);
    }
}

async function tienda() {
    const cli = new Cliente();
    if (!(await ingresar(cli, '4000048'))) return;
    const pag = await cli.get('tienda: página', '/tienda');
    const est = leerLivewire(pag.texto);
    while (est && ahora() < fin) { await lw(cli, 'tienda: sondeo (cada 2 s)', est); await dormir(2000); }
}

// ── Servidor, cola y orquestación ───────────────────────────────────────────
async function main() {
    mkdirSync('docs/qa', { recursive: true });
    compartido.sesion = preparar();

    const servidor = spawn('php', ['-S', `127.0.0.1:${PUERTO}`, '-t', resolve('public'), resolve('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
        cwd: resolve('public'), env: { ...process.env, ...ENTORNO, PHP_CLI_SERVER_WORKERS: String(WORKERS) }, stdio: ['ignore', 'ignore', 'ignore'],
    });
    const colas = [1, 2].map(() => spawn('php', ['artisan', 'queue:work', '--sleep=1', '--tries=1', '--max-time=3600'], { env: { ...process.env, ...ENTORNO }, stdio: 'ignore' }));
    const logAntes = (() => { try { return statSync('storage/logs/laravel.log').size; } catch { return 0; } })();
    for (let i = 0; i < 50; i++) { try { if ((await fetch(`${BASE}/up`)).ok) break; } catch { /* aún no */ } await dormir(200); }

    console.log(`· ${USUARIOS} participantes + 3 dirigentes + 1 tienda, ${SEGUNDOS} s, ${WORKERS} procesos PHP, sondeo cada ${SONDEO} s`);
    fin = SEGUNDOS;
    const inicioReal = performance.now();
    const vus = [];
    for (let i = 1; i <= USUARIOS; i++) vus.push(participante(i));
    vus.push(dirigente('4000045', 4000), dirigente('4000046', 4000), dirigente('4000047', 4000), tienda());

    // Lotes de cobros: cada uno vale 2 minutos; el primero llega antes del primer grupo de pagos y el segundo antes del último.
    (async () => {
        for (const [momento, n] of [[50, 60], [165, 60]]) {  // 60 cobros por lote: alcanza para los ~90 pagos
            while (ahora() < momento) await dormir(500);
            const tokens = await crearCobros(n);
            compartido.lotes.push({ creado: ahora(), tokens, siguiente: 0 });
        }
    })();

    // Avance cada 30 s
    const avance = setInterval(() => console.log(`  t=${Math.round(ahora())}s  peticiones=${[...metricas.values()].reduce((a, m) => a + m.n, 0)}  errores=${[...metricas.values()].reduce((a, m) => a + m.err, 0)}`), 30_000);
    await Promise.all(vus);
    clearInterval(avance);
    const duracion = (performance.now() - inicioReal) / 1000;

    // Dejar que la cola termine de procesar las fotos y medir cuánto tarda.
    console.log('· Esperando que la cola termine de procesar las fotos…');
    const inicioCola = performance.now();
    let pendientes = Number(tinker('echo DB::table("jobs")->count();'));
    while (pendientes > 0 && performance.now() - inicioCola < 180_000) { await dormir(1500); pendientes = Number(tinker('echo DB::table("jobs")->count();')); }
    const segundosCola = (performance.now() - inicioCola) / 1000;

    const db = JSON.parse(tinker(`
        echo json_encode([
            'asistencias' => DB::table('asistencias')->count(),
            'asistenciasDuplicadas' => DB::table('asistencias')->select('user_id', 'actividad_id')->groupBy('user_id', 'actividad_id')->havingRaw('count(*) > 1')->get()->count(),
            'cobrosPagados' => DB::table('cobros')->whereNotNull('pagado_at')->count(),
            'pagosEnLedger' => DB::table('mov_jlis')->where('origen_type', App\\Models\\Cobro::class)->count(),
            'cobrosPagadosMasDeUnaVez' => DB::table('mov_jlis')->where('origen_type', App\\Models\\Cobro::class)->select('origen_id')->groupBy('origen_id')->havingRaw('count(*) > 1')->get()->count(),
            'entregas' => DB::table('entregas')->count(),
            'entregasAprobadas' => DB::table('entregas')->where('estado', 'aprobada')->count(),
            'imagenes' => DB::table('entrega_imagenes')->count(),
            'imagenesProcesadas' => DB::table('entrega_imagenes')->whereNotNull('procesada_at')->count(),
            'reservas' => DB::table('reservas')->count(),
            'jobsFallidos' => DB::table('failed_jobs')->count(),
            'saldoNegativo' => DB::table('users')->where('jlis', '<', 0)->count(),
            'jlisTotal' => (int) DB::table('users')->where('ci', '>=', '6000001')->sum('jlis'),
        ]);
    `));
    const reconciliacion = php(['artisan', 'jota:reconciliar']).stdout ?? '';

    const logDespues = (() => { try { return readFileSync('storage/logs/laravel.log', 'utf8').slice(logAntes); } catch { return ''; } })();
    const erroresLog = (logDespues.match(/\.ERROR:/g) ?? []).length;

    servidor.kill(); colas.forEach((c) => c.kill());

    informar({ duracion, db, segundosCola, reconciliacion, erroresLog });
}

function informar({ duracion, db, segundosCola, reconciliacion, erroresLog }) {
    const filas = [...metricas].sort((a, b) => b[1].n - a[1].n).map(([e, m]) => ({ e, n: m.n, err: m.err, lim: m.limitado, p50: pct(m.lat, 50), p95: pct(m.lat, 95), p99: pct(m.lat, 99), max: Math.max(...m.lat) }));
    const total = filas.reduce((a, f) => a + f.n, 0);
    const errores = filas.reduce((a, f) => a + f.err, 0);
    const f = (x) => `${Math.round(x)} ms`;

    const verificaciones = [
        ['Asistencias en la base = escaneos exitosos', db.asistencias === contadores.scanOk, `${db.asistencias} vs ${contadores.scanOk}`],
        ['Ninguna asistencia duplicada (usuario × actividad)', db.asistenciasDuplicadas === 0, String(db.asistenciasDuplicadas)],
        ['Cobros pagados = pagos exitosos (los dobles toques no cobran dos veces)', db.cobrosPagados === contadores.pagoOk, `${db.cobrosPagados} vs ${contadores.pagoOk}`],
        ['Ningún cobro con más de un descuento', db.cobrosPagadosMasDeUnaVez === 0, String(db.cobrosPagadosMasDeUnaVez)],
        ['Pagos en el registro = cobros pagados', db.pagosEnLedger === db.cobrosPagados, `${db.pagosEnLedger} vs ${db.cobrosPagados}`],
        ['Entregas en la base = entregas enviadas', db.entregas === contadores.entregaOk, `${db.entregas} vs ${contadores.entregaOk}`],
        ['Aprobaciones en la base = aprobaciones hechas', db.entregasAprobadas === contadores.aprobadas, `${db.entregasAprobadas} vs ${contadores.aprobadas}`],
        ['Todas las fotos quedaron procesadas', db.imagenes === db.imagenesProcesadas, `${db.imagenesProcesadas}/${db.imagenes}`],
        ['Reservas en la base = reservas hechas', db.reservas === contadores.reservaOk, `${db.reservas} vs ${contadores.reservaOk}`],
        ['Ningún saldo de LisCoins negativo', db.saldoNegativo === 0, String(db.saldoNegativo)],
        ['Sin trabajos fallidos en la cola', db.jobsFallidos === 0, String(db.jobsFallidos)],
        ['jota:reconciliar sin diferencias', /Sin diferencias/.test(reconciliacion), reconciliacion.trim().split('\n').pop()],
        ['Sin errores en el registro de la aplicación', erroresLog === 0, `${erroresLog} error(es)`],
        ['Sin respuestas 5xx / 419 / caídas de conexión', errores === 0, `${errores} de ${total}`],
    ];

    const md = [
        `# Informe de la prueba de carga — ${new Date().toLocaleString('es-PY', { timeZone: 'America/Asuncion' })}`,
        '',
        `**Escenario:** ${USUARIOS} participantes + 3 dirigentes + 1 tienda conectados a la vez durante ${Math.round(duracion)} s (sondeo de pantallas cada ${SONDEO} s${SONDEO < 30 ? ' — ESTRÉS: más frecuente que en la app real' : ''}), contra el servidor incorporado de PHP con ${WORKERS} procesos y MySQL local, en una sola Mac. Es **más lento que Apache/PHP-FPM de producción** (sin caché de código y con la base compitiendo por la misma máquina): los tiempos son una cota pesimista.`,
        '',
        `**Total:** ${total} peticiones (${(total / duracion).toFixed(1)} por segundo), ${errores} con error de servidor.`,
        '',
        '## Tiempos por tipo de petición',
        '',
        '| Petición | N | Errores | 429 | p50 | p95 | p99 | máx |',
        '|---|---:|---:|---:|---:|---:|---:|---:|',
        ...filas.map((x) => `| ${x.e} | ${x.n} | ${x.err} | ${x.lim} | ${f(x.p50)} | ${f(x.p95)} | ${f(x.p99)} | ${f(x.max)} |`),
        '',
        '## Lo que hicieron los usuarios',
        '',
        `Ingresos ${contadores.ingresos} (fallidos ${contadores.ingresoFallido}) · escaneos ${contadores.scanOk} (ya registrados ${contadores.scanYa}, otros ${contadores.scanOtro}) · pagos ${contadores.pagoOk} (ya pagados por doble toque ${contadores.pagoYa}, otros ${contadores.pagoOtro}) · entregas ${contadores.entregaOk} (con foto ${contadores.entregaFoto}) · aprobadas ${contadores.aprobadas} · reservas ${contadores.reservaOk} (rechazadas ${contadores.reservaOtro}).`,
        '',
        `La cola terminó de procesar las fotos ${Math.round(segundosCola)} s después de terminar la prueba.`,
        '',
        '## Integridad después de la carga',
        '',
        '| Verificación | Resultado | Detalle |',
        '|---|:-:|---|',
        ...verificaciones.map(([t, ok, d]) => `| ${t} | ${ok ? '✅' : '❌'} | ${d} |`),
        '',
    ].join('\n');

    writeFileSync(SALIDA, md);
    console.log('\n' + md);
    process.exit(verificaciones.every(([, ok]) => ok) ? 0 : 1);
}

main().catch((e) => { console.error(e); process.exit(2); });
