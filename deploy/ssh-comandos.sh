#!/bin/bash
# Comandos que acepta la clave SSH limitada de Claude (ver docs/despliegue/clave-ssh-limitada.md).
#
# Se instala UNA vez FUERA de la carpeta del sitio (~/jota-ssh-comandos.sh) y se engancha en authorized_keys con
# `command="…",restrict`: el servidor ejecuta SIEMPRE este script, sin importar lo que pida el cliente, y nunca abre una
# consola libre. Cualquier cosa que no esté en la lista de abajo se rechaza. No lee .env ni archivos, no borra usuarios y no
# toca saldos: eso sigue siendo cosa de una persona con su propia sesión.
#
# Uso desde el Mac:   ssh -i ~/.ssh/jota_plesk naranjacom@64.176.19.59 <comando> [argumento]
set -u

PHP="${JOTA_PHP:-/opt/plesk/php/8.3/bin/php}"
APP="${JOTA_APP:-/var/www/vhosts/naranja.com.py/jotajoti.naranja.com.py}"
MAX_BUILD_BYTES=$((20 * 1024 * 1024))

# Lo que pidió el cliente, cortado en palabras sin interpretarlo como comando.
read -r CMD ARG ARG2 EXTRA <<< "${SSH_ORIGINAL_COMMAND:-}"

rechazar() { echo "Comando no permitido: $1" >&2; echo "Permitidos: deploy | verificar | respaldos | consultar <CI> | simular-eliminacion <CI> | actividad <palabra> [CI] | cola | subir-build (tar por stdin)" >&2; exit 1; }

[ -z "${EXTRA:-}" ] || rechazar "demasiados argumentos"
# Solo `actividad` admite un segundo argumento (el CI de la persona a probar).
[ -z "${ARG2:-}" ] || [ "${CMD:-}" = "actividad" ] || rechazar "demasiados argumentos"

case "${CMD:-}" in
    deploy)
        cd "$APP" && bash deploy-jota.sh
        ;;

    verificar)
        cd "$APP" && "$PHP" artisan jota:verificar-produccion
        ;;

    respaldos)
        cd "$APP" && "$PHP" artisan backup:list
        ;;

    # Solo lectura: datos y registros asociados de una persona, para decidir antes de corregir algo.
    consultar)
        [[ "${ARG:-}" =~ ^[0-9]{4,12}$ ]] || rechazar "consultar necesita un CI (solo números)"
        cd "$APP" && CI_BUSCADO="$ARG" "$PHP" artisan tinker --execute="$(cat <<'PHP'
$u = App\Models\User::where('ci', getenv('CI_BUSCADO'))->first();
if (! $u) { echo 'No existe un usuario con ese CI.'; return; }
$id = $u->id;
echo json_encode([
  'nombre' => $u->name, 'rol' => $u->rol->value, 'activada' => (bool) $u->activado_at,
  'puntos' => $u->puntos, 'liscoins' => $u->jlis,
  'mov_puntos' => DB::table('mov_puntos_individuales')->where('user_id', $id)->count(),
  'mov_liscoins' => DB::table('mov_jlis')->where('user_id', $id)->count(),
  'entregas' => DB::table('entregas')->where('user_id', $id)->count(),
  'reservas' => DB::table('reservas')->where('user_id', $id)->count(),
  'asistencias' => DB::table('asistencias')->where('user_id', $id)->count(),
  'contactos' => DB::table('contactos')->where('user_id', $id)->count(),
  'cobros' => DB::table('cobros')->where('creado_por', $id)->orWhere('pagado_por', $id)->count(),
  'penalizaciones' => DB::table('penalizaciones')->where('user_id', $id)->count(),
], JSON_UNESCAPED_UNICODE);
PHP
)"
        echo
        ;;

    # Solo SIMULACRO: muestra qué borraría y qué saldos corregiría `jota:eliminar-usuario-de-prueba`. Eliminar de verdad NO está acá:
    # lo corre una persona, con su sesión y escribiendo el CI para confirmar.
    simular-eliminacion)
        [[ "${ARG:-}" =~ ^[0-9]{4,12}$ ]] || rechazar "simular-eliminacion necesita un CI (solo números)"
        cd "$APP" && "$PHP" artisan jota:eliminar-usuario-de-prueba "$ARG" --simulacro
        ;;

    # Solo lectura: trabajos pendientes y fallidos de la cola (fotos), con el motivo del fallo.
    cola)
        cd "$APP" && "$PHP" artisan jota:cola
        ;;

    # Diagnóstico de un taller o estación: turnos, cupos y reservas; con un CI, prueba (y deshace) si esa persona podría apuntarse.
    actividad)
        [[ "${ARG:-}" =~ ^[[:alnum:]áéíóúñÁÉÍÓÚÑ._-]{2,40}$ ]] || rechazar "actividad necesita UNA palabra del título (sin espacios)"
        if [ -n "${ARG2:-}" ]; then
            [[ "$ARG2" =~ ^[0-9]{4,12}$ ]] || rechazar "el segundo argumento es un CI (solo números)"
            cd "$APP" && "$PHP" artisan jota:ver-actividad "$ARG" --ci="$ARG2"
        else
            cd "$APP" && "$PHP" artisan jota:ver-actividad "$ARG"
        fi
        ;;

    # Los estilos y scripts compilados llegan como un tar por la entrada estándar. Solo se acepta una carpeta build/ sin rutas
    # absolutas ni «..», de hasta 20 MB, y solo se escribe dentro de public/.
    subir-build)
        TMP="$(mktemp)"
        trap 'rm -f "$TMP"' EXIT
        head -c "$((MAX_BUILD_BYTES + 1))" > "$TMP"
        [ "$(wc -c < "$TMP")" -le "$MAX_BUILD_BYTES" ] || rechazar "el build pesa más de 20 MB"
        tar -tf "$TMP" > /dev/null 2>&1 || rechazar "el archivo no es un tar válido"
        if tar -tf "$TMP" | grep -qvE '^build(/[A-Za-z0-9._-]+)*/?$'; then rechazar "el tar tiene rutas que no son de build/"; fi
        tar -tf "$TMP" | grep -q '^build/manifest.json$' || rechazar "falta build/manifest.json"
        tar -xf "$TMP" -C "$APP/public" --no-same-owner && echo "Build subido a $APP/public/build"
        ;;

    *)
        rechazar "${CMD:-(vacío)}"
        ;;
esac
