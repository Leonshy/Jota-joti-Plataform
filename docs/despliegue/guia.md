# Despliegue en Plesk — jotajoti.naranja.com.py

Mismo método que Cateura: commit → push a GitHub → `git pull` en el servidor. Los comandos los corre una persona
por SSH. `public/build` no está en git y se sube aparte con `scp`.

| Dato | Valor |
|---|---|
| Servidor | `64.176.19.59`, puerto `22`, usuario `naranjacom` |
| Carpeta del proyecto | `/var/www/vhosts/naranja.com.py/jotajoti.naranja.com.py/` |
| Document Root (Plesk) | `…/jotajoti.naranja.com.py/public` |
| PHP | `/opt/plesk/php/8.3/bin/php` (8.3.35; el `php` por defecto del shell puede ser otro) |
| Composer | `/usr/local/psa/var/modules/composer/composer.phar` (el `/usr/local/bin/composer` es un wrapper: no sirve con `php`) |
| Repo | `https://github.com/Leonshy/Jota-joti-Plataform.git` (público, se clona por HTTPS) |

Alias para no repetir (en la sesión SSH):

```bash
alias php=/opt/plesk/php/8.3/bin/php
alias composer='/opt/plesk/php/8.3/bin/php /usr/local/psa/var/modules/composer/composer.phar'
```

## 0. Antes de empezar (en el panel de Plesk)

1. Suscripción/subdominio `jotajoti.naranja.com.py` creado, con PHP 8.3 (FPM).
2. Extensiones de PHP activas: `gd`, `intl`, `pdo_mysql`, `mbstring`, `fileinfo`, `zip`.
3. **Document Root = `public`** (Hosting y DNS → Apache & nginx, o Configuración de hosting). Si queda en la raíz, el
   `.env` queda expuesto por la web.
4. SSL: Let's Encrypt para el subdominio, con «Redirigir de HTTP a HTTPS».
5. Base de datos MySQL creada (utf8mb4). Anotar nombre, usuario y contraseña; no se comparten por chat.

## 1. Primer despliegue (una sola vez)

```bash
ssh naranjacom@64.176.19.59
cd /var/www/vhosts/naranja.com.py/jotajoti.naranja.com.py/
ls -la            # si hay archivos de relleno de Plesk (index.html, etc.), borrarlos: el clone pide carpeta vacía

git clone https://github.com/Leonshy/Jota-joti-Plataform.git .
composer install --no-dev --optimize-autoloader --no-interaction

cp .env.production.example .env
php artisan key:generate           # escribe APP_KEY en el .env
nano .env                          # APP_URL=https://jotajoti.naranja.com.py y DB_DATABASE / DB_USERNAME / DB_PASSWORD
chmod 600 .env
```

Subir los estilos y scripts, **desde la Mac** (en la carpeta del repo):

```bash
npm ci && npm run build
scp -r public/build/ naranjacom@64.176.19.59:/var/www/vhosts/naranja.com.py/jotajoti.naranja.com.py/public/
```

De vuelta en el servidor:

```bash
php artisan migrate --force
php artisan storage:link
php artisan jota:crear-admin       # contraseña larga; NO correr db:seed (es solo de prueba)
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan jota:verificar-produccion
```

El último comando debe terminar sin problemas críticos. Si marca algo, se corrige antes de abrir el evento.

## 2. Tareas programadas (Plesk → Tareas programadas, cada una con el PHP 8.3)

| Frecuencia | Comando |
|---|---|
| Cada minuto | `/opt/plesk/php/8.3/bin/php /var/www/vhosts/naranja.com.py/jotajoti.naranja.com.py/artisan schedule:run` |
| Cada minuto | `/opt/plesk/php/8.3/bin/php /var/www/vhosts/naranja.com.py/jotajoti.naranja.com.py/artisan queue:work --stop-when-empty --max-time=55` |

- `schedule:run` dispara lo que define `routes/console.php`: respaldo de la base cada hora (`:00`), `jota:reconciliar`
  (`:10`), `backup:monitor` (`:30`) y la limpieza de respaldos viejos a las 03:30.
- `queue:work` procesa las fotos de las entregas (sin él quedan «procesando»).

## 2b. Respaldo de la base

- Cada hora se guarda un `.zip` **cifrado** en `storage/app/private/JOTA-JOTI 2026/` (solo la base, el volcado va en gzip).
- La contraseña es `BACKUP_ARCHIVE_PASSWORD` del `.env`. Generala con `php -r "echo bin2hex(random_bytes(24));"` y **guardala
  fuera del servidor** (gestor de contraseñas): sin ella el respaldo no se puede abrir.
- Retención: todos por 7 días, uno por día hasta 16 días, luego semanales (se limpia solo, tope de 5 GB).
- `jota:verificar-produccion` marca error si falta la contraseña. Si `mysqldump` no está en el PATH, poné su carpeta en `DB_DUMP_PATH`.
- Probar una vez en el servidor: `php artisan backup:run --only-db` y `php artisan backup:list` (debe verse «Healthy»).
- **Copia fuera del servidor:** un respaldo en el mismo servidor no sirve si el servidor se pierde. Antes del evento, y al
  cerrarlo, bajar el último a la Mac: `scp "naranjacom@64.176.19.59:/var/www/vhosts/naranja.com.py/jotajoti.naranja.com.py/storage/app/private/JOTA-JOTI 2026/*.zip" ~/respaldos-jota/`
- **Restaurar:** descomprimir el zip con la contraseña (7-Zip/Keka; el `unzip` de macOS no abre zips con AES), y luego
  `gunzip -c db-dumps/mysql-<base>.sql.gz | mysql -u USUARIO -p BASE`.

## 3. Actualizaciones

En la Mac: `git push origin main`; si cambió CSS/JS, `npm run build` y el `scp` de `public/build/`. En el servidor:

```bash
cd /var/www/vhosts/naranja.com.py/jotajoti.naranja.com.py/
bash deploy-jota.sh
```

Congelamiento: sin despliegues desde 48 h antes del evento hasta su cierre (PLAN, Fase 11).

## 4. Problemas conocidos

- **Página en blanco o 500:** revisar `storage/logs/` y los permisos (`storage/` y `bootstrap/cache/` escribibles por el
  usuario del sitio).
- **Enlaces o QR con `http://`:** confirmar `TRUSTED_PROXIES=*` y `APP_URL` con `https://`.
- **Los QR de asistencia y cobro salen con otro dominio:** viene de `APP_URL`; después de cambiarlo, `php artisan config:cache`.
- **Fotos que no terminan de procesarse:** falta el cron de la cola o hay trabajos fallidos (`php artisan queue:failed`).
