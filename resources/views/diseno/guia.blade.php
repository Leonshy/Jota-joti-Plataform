<x-layouts.movil titulo="Guía de estilo">
    <div class="mx-auto max-w-3xl space-y-14 px-4 py-10">
        <header class="flex flex-col gap-4">
            <x-ui.logo :ancho="260" />
            <h1 class="text-3xl text-titulo">Guía de estilo</h1>
            <p class="max-w-prose text-texto-suave">Sistema de diseño de la plataforma, basado en el estilo visual de jotajoti.info. Todo sale de los tokens de <code>resources/css/app.css</code>. Vista previa con datos de ejemplo; no existe en producción.</p>
        </header>

        <section aria-labelledby="g-color" class="space-y-4">
            <h2 id="g-color" class="text-2xl">Color</h2>
            <p class="max-w-prose text-texto-suave">Violeta para la acción; azul para enlaces; cian, rosa y amarillo para sellos y recompensas; verde y rojo para estados. Fondo claro porque se usa al aire libre.</p>
            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Violeta 600', '#622599', 'bg-violeta-600 text-white', 'Acción, títulos'],
                    ['Violeta 700', '#4E1E7A', 'bg-violeta-700 text-white', 'Hover del botón'],
                    ['Violeta 900', '#261940', 'bg-violeta-900 text-white', 'Texto sobre cian'],
                    ['Violeta 100', '#EBEBFF', 'bg-violeta-100 text-gris-900', 'Realce suave'],
                    ['Azul 700', '#00639A', 'bg-azul-700 text-white', 'Enlaces'],
                    ['Azul 600', '#0079C0', 'bg-azul-600 text-white', 'Foco, acento'],
                    ['Cian 300', '#82E6DE', 'bg-cian-300 text-gris-900', 'Info, «¡sumaste!»'],
                    ['Rosa 300', '#FF8DFF', 'bg-rosa-300 text-gris-900', 'Recompensa'],
                    ['Amarillo 400', '#F9B700', 'bg-amarillo-400 text-gris-900', 'Aviso, 1.er puesto'],
                    ['Verde 600', '#248737', 'bg-verde-600 text-white', 'Éxito'],
                    ['Rojo 700', '#B3261E', 'bg-rojo-700 text-white', 'Error (texto)'],
                    ['Rojo 400', '#FF5655', 'bg-rojo-400 text-gris-900', 'Error (relleno)'],
                    ['Naranja 500', '#EF5000', 'bg-naranja-500 text-gris-900 text-xl!', 'Solo grande'],
                    ['Gris 900', '#212529', 'bg-gris-900 text-white', 'Texto'],
                    ['Gris 600', '#5B636A', 'bg-gris-600 text-white', 'Texto de apoyo'],
                    ['Lavanda', '#F7F7FF', 'bg-violeta-50 text-gris-900 ring-1 ring-borde', 'Fondo suave'],
                ] as [$nombre, $hex, $clases, $uso])
                    <li class="overflow-hidden rounded-md shadow-tarjeta">
                        <div class="{{ $clases }} flex h-20 items-end p-3 text-sm font-bold">{{ $nombre }}</div>
                        <div class="bg-superficie p-3"><p class="numeros text-sm font-bold">{{ $hex }}</p><p class="text-xs text-texto-suave">{{ $uso }}</p></div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="g-tipo" class="space-y-4">
            <h2 id="g-tipo" class="text-2xl">Tipografía</h2>
            <p class="max-w-prose text-texto-suave">Poppins Bold para títulos y botones; Poppins Medium para el resto. Escala fija en rem. El cuerpo nunca baja de 16 px; la ayuda, de 14 px.</p>
            <div class="space-y-3 rounded-md bg-superficie p-5 shadow-tarjeta">
                <p class="text-4xl font-bold text-titulo">Puntos 1.250</p>
                <p class="text-3xl font-bold">Ranking de Tropa</p>
                <p class="text-2xl font-bold">Hola, Ana</p>
                <p class="text-xl font-bold">Nudos en equipo</p>
                <p class="text-lg font-bold">Para vos</p>
                <p class="text-base">Sacate una foto con tu uniforme y contanos de dónde venís.</p>
                <p class="text-sm text-texto-suave">Hasta 2000 caracteres.</p>
                <p class="text-xs text-texto-suave">Vence a las 18:00 · 4 fotos como máximo</p>
                <p class="numeros text-2xl font-bold">0123456789 <span class="text-base text-texto-suave">(números tabulares)</span></p>
            </div>
        </section>

        <section aria-labelledby="g-botones" class="space-y-4">
            <h2 id="g-botones" class="text-2xl">Botones</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-ui.boton>Primario</x-ui.boton>
                <x-ui.boton icono-final="arrow-right">Con ícono</x-ui.boton>
                <x-ui.boton variante="secundario">Secundario</x-ui.boton>
                <x-ui.boton variante="peligro">Rechazar</x-ui.boton>
                <x-ui.boton :cargando="true">Enviando</x-ui.boton>
                <x-ui.boton :deshabilitado="true">Deshabilitado</x-ui.boton>
                <x-ui.boton variante="enlace" :bloque="false" icono-final="arrow-right" class="justify-self-start">Enlace de texto</x-ui.boton>
            </div>
            <p class="text-sm text-texto-suave">Píldora de 56 px (el principal), con foco visible, estado activo y deshabilitado sin depender solo del color.</p>
        </section>

        <section aria-labelledby="g-campos" class="space-y-4">
            <h2 id="g-campos" class="text-2xl">Campos</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.campo name="g1" label="Tu número de CI" valor="1234567" ayuda="Solo números, sin puntos." />
                <x-ui.campo name="g2" label="Tu PIN" :pin="true" valor="1234" />
                <x-ui.campo name="g3" label="Tu PIN" :pin="true" error="PIN incorrecto. Probá de nuevo." />
                <x-ui.campo name="g4" label="Contanos qué hiciste" :area="true" :filas="3" valor="Soy Ana, de Asunción." />
            </div>
        </section>

        <section aria-labelledby="g-estados" class="space-y-4">
            <h2 id="g-estados" class="text-2xl">Insignias y avisos</h2>
            <div class="flex flex-wrap gap-2">
                <x-ui.insignia tono="marca">Nueva</x-ui.insignia>
                <x-ui.insignia tono="aviso">Esperando revisión</x-ui.insignia>
                <x-ui.insignia tono="exito">Aprobada</x-ui.insignia>
                <x-ui.insignia tono="error">Rechazada</x-ui.insignia>
                <x-ui.insignia tono="neutro">Vencida</x-ui.insignia>
                <x-ui.insignia tono="info">20 puntos</x-ui.insignia>
            </div>
            <div class="grid gap-3">
                <x-ui.aviso tono="info" titulo="Para saber">Los empates comparten puesto.</x-ui.aviso>
                <x-ui.aviso tono="exito">¡Aprobada! Sumaste 10 puntos y 10 LisCoins.</x-ui.aviso>
                <x-ui.aviso tono="aviso">Esperando revisión. Tu dirigente la va a mirar pronto.</x-ui.aviso>
                <x-ui.aviso tono="error">No te alcanzan los LisCoins. Te faltan 25. No se descontó nada.</x-ui.aviso>
            </div>
        </section>

        <section aria-labelledby="g-mov" class="space-y-4">
            <h2 id="g-mov" class="text-2xl">Movimiento</h2>
            <p class="max-w-prose text-texto-suave">Tres momentos con animación propia, todo lo demás es rápido (150–220 ms, salida exponencial). Con «reducir movimiento» activado se ven los mismos cambios, sin desplazamientos.</p>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-md bg-superficie p-4 shadow-tarjeta">
                    <h3 class="text-base">Pago confirmado</h3>
                    <div id="demo-pago" class="my-2"><x-ui.resultado tono="exito" titulo="¡Pago listo!" class="py-2" /></div>
                    <x-ui.boton variante="secundario" data-repetir="#demo-pago" class="min-h-tactil! text-sm!">{{ __('jota.diseno.repetir') }}</x-ui.boton>
                </div>
                <div class="rounded-md bg-superficie p-4 shadow-tarjeta">
                    <h3 class="text-base">Puntos acreditados</h3>
                    <div id="demo-puntos" class="my-3 grid grid-cols-2 gap-2">
                        <x-ui.tarjeta tono="cian"><p class="text-sm">Puntos</p><p class="numeros text-3xl font-bold text-violeta-900"><span data-contar data-desde="10" data-hasta="30">30</span></p></x-ui.tarjeta>
                        <x-ui.tarjeta tono="rosa"><p class="text-sm">LisCoins</p><p class="numeros text-3xl font-bold text-violeta-900"><span data-contar data-desde="70" data-hasta="90">90</span></p></x-ui.tarjeta>
                    </div>
                    <x-ui.boton variante="secundario" data-repetir="#demo-puntos" class="min-h-tactil! text-sm!">{{ __('jota.diseno.repetir') }}</x-ui.boton>
                </div>
                <div class="rounded-md bg-superficie p-4 shadow-tarjeta">
                    <h3 class="text-base">QR que rota</h3>
                    <div class="my-3"><x-ui.qr :svg="$qr" :rotar="true" :ciclo="6" class="max-w-40" /></div>
                    <p class="text-xs text-texto-suave">Acá rota cada 6 s para verlo; en el evento, cada 60 s.</p>
                </div>
            </div>
        </section>

        <section aria-labelledby="g-pantallas" class="space-y-4">
            <h2 id="g-pantallas" class="text-2xl">Pantallas (360 px)</h2>
            <ul class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach ($pantallas as $p)
                    <li><a href="{{ url('/diseno/'.$p) }}" class="flex min-h-tactil items-center rounded-sm bg-primario-suave px-4 py-2 font-bold text-primario no-underline hover:bg-violeta-100">{{ $p }}</a></li>
                @endforeach
            </ul>
        </section>
    </div>
</x-layouts.movil>
