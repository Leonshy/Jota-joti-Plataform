{{-- Pantalla pública que el dirigente proyecta (enlace firmado, sin sesión). Debe leerse a ~3 metros. --}}
<x-layouts.movil :titulo="__('jota.dirigente.asistencia_titulo').' · '.$actividad">
    <main id="contenido" tabindex="-1" class="mx-auto flex min-h-dvh w-full max-w-md flex-col items-center justify-center gap-6 bg-fondo px-5 py-8 text-center focus:outline-none md:max-w-2xl">
        <x-ui.logo :ancho="160" />
        <h1 class="text-xl">{{ $actividad }}</h1>

        <div id="visor" class="mx-auto w-full max-w-80 md:max-w-lg">
            <div class="rounded-md bg-white p-4 shadow-elevada">
                <div id="qr" class="aspect-square w-full [&>svg]:size-full" role="img" aria-label="{{ __('jota.escanear.qr_publica_alt') }}"></div>
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-primario-suave" aria-hidden="true">
                <div id="barra" class="h-full origin-left rounded-full bg-primario"></div>
            </div>
        </div>

        <div id="reloj">
            <p id="cuenta" class="numeros text-4xl font-bold text-titulo" aria-live="off">0:00</p>
            <p class="text-sm text-texto-suave">{{ __('jota.dirigente.qr_cambia') }}</p>
        </div>

        <p id="estado" role="status" class="max-w-xs text-lg">{{ __('jota.dirigente.qr_instruccion') }}</p>

        {{-- Solo si se llegó desde la app del dirigente: en un proyector abierto directo no hay a dónde volver. --}}
        <x-ui.boton variante="secundario" type="button" icono="arrows-pointing-in" id="volver" hidden onclick="history.back()">{{ __('jota.dirigente.qr_salir') }}</x-ui.boton>
    </main>

    <script>
        const urlQr = @json($urlQr);
        const textos = @json(['cerrada' => __('jota.escanear.asistencia_cerrada_titulo'), 'sinConexion' => __('jota.dirigente.qr_sin_conexion'), 'instruccion' => __('jota.dirigente.qr_instruccion')]);
        const qr = document.getElementById('qr');
        const barra = document.getElementById('barra');
        const cuenta = document.getElementById('cuenta');
        const estado = document.getElementById('estado');
        const reducir = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let reloj = null;

        function iniciarCuenta(segundos) {
            clearInterval(reloj);
            let restante = segundos;
            const pintar = () => { cuenta.textContent = Math.floor(restante / 60) + ':' + String(restante % 60).padStart(2, '0'); };
            pintar();
            reloj = setInterval(() => { restante = Math.max(0, restante - 1); pintar(); }, 1000);

            // La barra se vacía en forma lineal hasta el próximo código (movimiento constante).
            barra.style.setProperty('--inicio', Math.min(segundos / 60, 1));
            barra.style.animation = 'none';
            void barra.offsetWidth;
            barra.style.animation = 'qr-cuenta ' + segundos + 's linear forwards';
        }

        function mostrar(svg) {
            if (qr.innerHTML === '' || reducir()) { qr.innerHTML = svg; return; }
            qr.classList.add('animate-qr-sale');
            setTimeout(() => {
                qr.classList.remove('animate-qr-sale');
                qr.innerHTML = svg;
                qr.classList.add('animate-qr-entra');
                setTimeout(() => qr.classList.remove('animate-qr-entra'), 300);
            }, 160);
        }

        async function cargar() {
            try {
                const respuesta = await fetch(urlQr, { headers: { Accept: 'application/json' }, cache: 'no-store' });

                if (respuesta.status === 410) {
                    clearInterval(reloj);
                    qr.innerHTML = '';
                    document.getElementById('visor').hidden = true;
                    document.getElementById('reloj').hidden = true;
                    estado.textContent = textos.cerrada;
                    return;
                }

                const datos = await respuesta.json();
                mostrar(datos.svg);
                estado.textContent = textos.instruccion;
                iniciarCuenta(datos.segundos);
                setTimeout(cargar, datos.segundos * 1000 + 250);
            } catch (e) {
                estado.textContent = textos.sinConexion;
                setTimeout(cargar, 3000);
            }
        }

        if (history.length > 1) document.getElementById('volver').hidden = false;

        cargar();
    </script>
</x-layouts.movil>
