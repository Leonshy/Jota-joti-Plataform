# Despliegue en Plesk — jota-joti.naranja.com.py

Mismo método que Cateura: commit → push a GitHub → `git pull` en el servidor. Los comandos los corre una persona
por SSH. `public/build` no está en git y se sube aparte con `scp`.

| Dato | Valor |
|---|---|
| Servidor | `64.176.19.59`, puerto `22`, usuario `naranjacom` |
| Carpeta del proyecto | `/var/www/vhosts/naranja.com.py/jota-joti.naranja.com.py/` |
| Document Root (Plesk) | `…/jota-joti.naranja.com.py/public` |
| PHP | `/opt/plesk/php/8.3/bin/php` (8.3.35; el `php` por defecto del shell puede ser otro) |
| Composer | `/usr/local/psa/var/modules/composer/composer.phar` (el `/usr/local/bin/composer` es un wrapper: no sirve con `php`) |
| Repo | `https://github.com/Leonshy/Jota-joti-Plataform.git` (público, se clona por HTTPS) |

Alias para no repetir (en la sesión SSH):

```bash
alias php=/opt/plesk/php/8.3/bin/php
alias composer='/opt/plesk/php/8.3/bin/php /usr/local/psa/var/modules/composer/composer.phar'
```

## 0. Antes de empezar (en el panel de Plesk)

1. Suscripción/subdominio `jota-joti.naranja.com.py` creado, con PHP 8.3 (FPM).
2. Extensiones de PHP activas: `gd`, `intl`, `pdo_mysql`, `mbstring`, `fileinfo`, `zip`.
3. **Document Root = `public`** (Hosting y DNS → Apache & nginx, o Configuración de hosting). Si queda en la raíz, el
   `.env` queda expuesto por la web.
4. SSL: Let's Encrypt para el subdominio, con «Redirigir de HTTP a HTTPS».
5. Base de datos MySQL creada (utf8mb4). Anotar nombre, usuario y contraseña; no se comparten por chat.

## 1. Primer despliegue (una sola vez)

```bash
ssh naranjacom@64.176.19.59
cd /var/www/vhosts/naranja.com.py/jota-joti.naranja.com.py/
ls -la            # si hay archivos de relleno de Plesk (index.html, etc.), borrarlos: el clone pide carpeta vacía

git clone https://github.com/Leonshy/Jota-joti-Plataform.git .
composer install --no-dev --optimize-autoloader --no-interaction

cp .env.production.example .env
php artisan key:generate           # escribe APP_KEY en el .env
nano .env                          # APP_URL=https://jota-joti.naranja.com.py y DB_DATABASE / DB_USERNAME / DB_PASSWORD
chmod 600 .env
```

Subir los estilos y scripts, **desde la Mac** (en la carpeta del repo):

```bash
npm ci && npm run build
scp -r public/build/ naranjacom@64.176.19.59:/var/www/vhosts/naranja.com.py/jota-joti.naranja.com.py/public/
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
| Cada minuto | `/opt/plesk/php/8.3/bin/php /var/www/vhosts/naranja.com.py/jota-joti.naranja.com.py/artisan queue:work --stop-when-empty --max-time=55` |
| Cada hora (durante el evento) | `/opt/plesk/php/8.3/bin/php /var/www/vhosts/naranja.com.py/jota-joti.naranja.com.py/artisan jota:reconciliar` |

El primero procesa las fotos de las entregas (sin él quedan «procesando»). `laravel-backup` todavía no está instalado: hasta
que se configure, pedir un respaldo de la base desde Plesk (Bases de datos → Exportar volcado) o programarlo ahí.

## 3. Actualizaciones

En la Mac: `git push origin main`; si cambió CSS/JS, `npm run build` y el `scp` de `public/build/`. En el servidor:

```bash
cd /var/www/vhosts/naranja.com.py/jota-joti.naranja.com.py/
bash deploy-jota.sh
```

Congelamiento: sin despliegues desde 48 h antes del evento hasta su cierre (PLAN, Fase 11).

## 4. Problemas conocidos

- **Página en blanco o 500:** revisar `storage/logs/` y los permisos (`storage/` y `bootstrap/cache/` escribibles por el
  usuario del sitio).
- **Enlaces o QR con `http://`:** confirmar `TRUSTED_PROXIES=*` y `APP_URL` con `https://`.
- **Los QR de asistencia y cobro salen con otro dominio:** viene de `APP_URL`; después de cambiarlo, `php artisan config:cache`.
- **Fotos que no terminan de procesarse:** falta el cron de la cola o hay trabajos fallidos (`php artisan queue:failed`).
