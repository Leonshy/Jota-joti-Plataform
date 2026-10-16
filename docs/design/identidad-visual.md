# Identidad visual — investigación de jotajoti.info

Fuente: el sitio oficial del **JOTA-JOTI 2026** (https://jotajoti.info), analizado el 15/10/2026 a partir de su
HTML, sus hojas de estilo y capturas en pantalla. Decisión del proyecto: **la plataforma usa este estilo
visual.** El logo oficial tiene uso autorizado (confirmado por el responsable del proyecto).

## Lo que define el estilo

| Rasgo | Qué hace el sitio | Dato medido |
|---|---|---|
| **Color principal** | Violeta scout en titulares, navegación, botones y enlaces de marca | `#622599` (token `--primary`) |
| **Color secundario** | Azul para enlaces y llamadas a la acción de texto («Explore the Map →») | `#0079C0` |
| **Acentos** | Cian suave, rosa, amarillo, verde y naranja del logo, usados como **fondos de bloques** y sellos | `#82E6DE` cian · `#FF8DFF` rosa · `#F9B700` amarillo · `#248737` verde · `#EF5000` naranja · `#0094B4` turquesa |
| **Neutros** | Texto casi negro, grises fríos, fondos lavanda muy suaves | `#212529` · `#343A40` · `#6C757D` · `#F8F9FA` · `#F7F7FF` · `#EBEBFF` |
| **Violetas profundos** | Para texto de énfasis y fondos oscuros de marca | `#4D006E` · `#261940` |
| **Tipografía** | **Poppins** únicamente: *Bold* (700) para títulos, botones y énfasis; *Medium* (500) para el cuerpo | cuerpo 18 px / 24 px; h1 48 px, h2 32 px (escritorio) |
| **Botones** | **Píldora completa** (radio máximo), violeta lleno con texto blanco en negrita; secundarios con contorno | hover `#4E1E7A` |
| **Tarjetas y bloques** | Blancos con esquinas muy redondeadas sobre fotos o fondos de color | radio dominante **8 px**; también 16 px y 32 px |
| **Imágenes** | Fotos reales y cálidas de scouts, ilustraciones isométricas coloridas, marco de color con esquinas redondeadas | — |
| **Énfasis en el texto** | Dentro de una frase en gris, **las palabras clave van en violeta y negrita** | — |
| **Enlaces de texto** | Azul, con flecha → al final | `#0079C0`, hover `#00395A` |
| **Tono de voz** | Alegre y directo; frases cortas, imperativo, segunda persona, con signos de exclamación y algún emoji | «JOTA-JOTI 2026 is here. Get ready!» |
| **Logo** | Letras gruesas y lúdicas: *JOTA* en naranja y amarillo, guion violeta, *JOTI* en turquesa y cian, con una flor de lis violeta y una flecha amarilla | `logo.svg` 1000 × 203 |

## Qué adoptamos, qué adaptamos y qué no tomamos

**Adoptamos tal cual**
- La paleta de marca (violeta, azul, cian, rosa, amarillo, verde, naranja) y los neutros.
- Poppins Bold y Medium, autohospedadas (la fuente es libre, licencia SIL OFL). Las dos variantes del sitio
  cubren todo el español (tildes, eñes, ¿ ¡).
- Botones en píldora, radios de 8 / 16 px, énfasis violeta dentro del texto, enlaces con flecha.
- El logo oficial del JOTA-JOTI 2026 (archivo del propio sitio).

**Adaptamos por accesibilidad y uso real** (celular rayado, sol fuerte, una mano)
- **Rojo de error**: `#FF5655` da solo 3,1:1 sobre blanco. Para texto se usa un rojo más oscuro
  (`#B3261E`, 6,5:1); `#FF5655` queda como relleno con texto oscuro (4,9:1).
- **Amarillo** `#F9B700` sobre blanco no se lee (1,8:1): solo como relleno con texto `#212529` (8,7:1). Para
  texto de aviso se usa un ámbar oscuro (`#7A5800`).
- **Naranja** `#EF5000` (3,6:1) solo para elementos grandes o decorativos, nunca texto chico.
- **Cuerpo de texto**: el sitio usa 18 px; la app usa 16 px con interlineado amplio, porque en una pantalla
  de celular con datos densos (saldos, listas) rinde mejor. Nada del cuerpo baja de 16 px; las etiquetas de
  apoyo, de 14 px.
- **Objetivos táctiles** de 48 px como mínimo y botones principales de 56 px.
- La paleta clara del sitio se mantiene: **fondo claro** porque el uso real es al aire libre, bajo el sol.

**No tomamos**
- Las **fotos del sitio** (muestran a menores de edad) ni las ilustraciones del mapa.
- Los logos de terceros (World Scout Crest Trust, ONGY, etc.).
- El tono bilingüe inglés/francés: toda la interfaz va en español paraguayo con voseo.
- El menú superior con desplegables: la app móvil usa la barra inferior ya aprobada en los wireframes.

## Contraste verificado (WCAG 2.2 AA)

| Combinación | Ratio | Uso permitido |
|---|---|---|
| Violeta `#622599` sobre blanco / blanco sobre violeta | 9,4:1 | Texto y botones |
| Azul `#0079C0` sobre blanco | 4,7:1 | Enlaces y texto |
| Verde `#248737` sobre blanco | 4,6:1 | Texto y relleno de éxito |
| Gris `#6C757D` sobre blanco | 4,7:1 | Texto secundario |
| `#212529` sobre cian `#82E6DE` | 10,5:1 | Bloques de aviso y de «¡sumaste!» |
| Violeta sobre cian | 6,4:1 | Títulos sobre bloque cian |
| `#212529` sobre amarillo `#F9B700` | 8,7:1 | Relleno de advertencia |
| `#212529` sobre rojo `#FF5655` | 4,9:1 | Relleno de error |
| Rojo `#B3261E` sobre blanco | 6,5:1 | Texto de error |
| Ámbar `#7A5800` sobre `#FFF4CC` | 5,9:1 | Texto de advertencia |
