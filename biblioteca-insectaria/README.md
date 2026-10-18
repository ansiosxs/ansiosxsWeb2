# Biblioteca Comunitaria Insectaria

Monolito MVC con Laravel 12, PHP 8.4, MySQL/InnoDB, vistas Blade y Vue 3,
compilado localmente con Vite y Tailwind CSS 4. Está separado del sitio estático
conservado en `../web-estatica`; se puede publicar como subdominio, por ejemplo
`biblioteca.tudominio.cl`.

## Requisitos

- PHP 8.2 o superior (recomendado PHP 8.4) con `pdo_mysql`, `mbstring`,
  `openssl`, `fileinfo`, `xml` y `ctype`.
- Composer 2.
- MySQL/MariaDB compatible con InnoDB.
- Node.js 20 y npm solo para compilar los assets localmente; no se requiere en
  el hosting.

Laravel 11 indicado en la versión inicial de la especificación no se usa: al
instalarlo Composer bloqueó las versiones disponibles por avisos de seguridad.
Laravel 12 mantiene la arquitectura solicitada y funciona con PHP 8.4.

## Instalación local

Desde esta carpeta:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
composer install
npm ci
php artisan key:generate
```

El proyecto conserva la base local en SQLite; configura estas entradas en
`.env` para desarrollo:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

El archivo local `database/database.sqlite` guarda los datos de desarrollo y
no debe subirse al hosting. Luego ejecuta:

```powershell
php artisan migrate
php artisan biblioteca:crear-voluntario
npm run build
php artisan serve --port=8001
```

La migración más reciente consolida los ejemplares en una cantidad por libro y
elimina la identificación individual; aplica `php artisan migrate --force` al
actualizar una instalación existente.

Abre `http://127.0.0.1:8001/login` e ingresa con la cuenta creada. Las pruebas
automatizadas usan SQLite en memoria:

```powershell
php artisan test
```

El comando para crear voluntarios solicita la contraseña de forma oculta y
exige al menos 12 caracteres. No hay registro público de cuentas. También se
puede crear el primer usuario con el seeder si se establecen temporalmente
`BIBLIOTECA_ADMIN_NOMBRE`, `BIBLIOTECA_ADMIN_EMAIL` y
`BIBLIOTECA_ADMIN_PASSWORD` en el entorno antes de `php artisan db:seed`; el
password se almacena con el cast `hashed` de Laravel.

Para producción, crea el `.env` del servidor a partir de
`.env.production.example`, completa dominio, credenciales MySQL y SMTP del
proveedor, y ejecuta `php artisan key:generate` en el servidor para crear allí
una `APP_KEY` distinta. Nunca subas el `.env` local ni esta plantilla con
marcadores sin reemplazar. No copies la base SQLite local a producción.

## Interfaz administrativa

El panel usa componentes Blade, utilidades de Tailwind CSS y Alpine.js
inspirados en [TailAdmin Laravel](https://github.com/TailAdmin/tailadmin-laravel).
La navegación, el encabezado y las tarjetas se adaptaron a los colores y
tipografías de `web-estatica`. TailAdmin se distribuye bajo licencia MIT; se
conserva una copia en
[`resources/third_party/tailadmin/LICENSE`](resources/third_party/tailadmin/LICENSE).

## Funcionalidades implementadas

- Acceso de voluntariado, cierre de sesión y bloqueo de credenciales tras cinco
  intentos fallidos en un minuto.
- CRUD de libros con inventario por cantidades, estanterías y anuncios.
- Socios con RUN chileno validado/normalizado, teléfono celular chileno
  normalizado, inscripción y renovación anual.
- Préstamos transaccionales por libro y cantidad, con `lockForUpdate`, plazo
  configurable, límites de unidades por socio, morosidad y UUID idempotente.
- Devoluciones parciales por préstamo, días de atraso, lista de morosidad, lista telefónica y
  recordatorios de correo registrados para no repetirlos.
- Portal público Vue, API de catálogo sin información de socios, anuncios por
  fecha, búsqueda paginada y caché de la última consulta
  para uso sin conexión.
- Borradores locales y reintento visible ante pérdida de red o errores 5xx en
  los formularios de préstamo/devolución. Las escrituras no se almacenan en el
  service worker ni se envían en segundo plano.
- Resumen estadístico agregado sin exponer fichas individuales de socios.

## Variables principales

| Variable | Uso |
| --- | --- |
| `APP_ENV`, `APP_DEBUG`, `APP_KEY`, `APP_URL` | Entorno, modo depuración, clave generada y URL HTTPS. |
| `APP_TIMEZONE` | Zona horaria; por defecto `America/Santiago`. |
| `APP_LOCALE` | Idioma; por defecto `es`. |
| `DB_*` | Conexión MySQL del hosting. |
| `MAIL_*` | Servidor SMTP para recordatorios. |
| `BIBLIOTECA_PLAZO_PRESTAMO_DIAS` | Días de préstamo; por defecto 14. |
| `BIBLIOTECA_MAX_PRESTAMOS_ACTIVOS` | Máximo por socio; por defecto 2. |
| `BIBLIOTECA_DIAS_RECORDATORIO` | Anticipación del aviso; por defecto 2. |
| `APP_PUBLIC_PATH` | Opcional: ruta absoluta del document root si no se puede apuntar a `public/`. |

No subas `.env` al repositorio ni habilites `APP_DEBUG` en producción.

## Despliegue en hosting compartido

1. En local ejecuta pruebas y compila los archivos:

   ```powershell
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```

2. Crea una base MySQL **vacía** en Ferozo. Si tienes terminal, ejecuta
   `php artisan migrate --force`. Si no la tienes, selecciona esa base en
   phpMyAdmin e importa una vez el archivo `database/mysql-hosting.sql` del
   paquete de producción, que crea las tablas de Laravel y de la biblioteca y
   registra las migraciones como aplicadas. No importes ese archivo sobre
   tablas existentes ni uses/exportes la SQLite local como esquema MySQL.
   Si una primera importación falló y dejó tablas parciales, confirma que no
   contengan datos y elimina únicamente las tablas Laravel/Biblioteca antes de
   reimportar el esquema; nunca uses `DROP DATABASE` en una base compartida.
3. Mantén el proyecto, `.env`, `vendor/`, `storage/` y `bootstrap/` fuera de la
   carpeta pública. La opción recomendada es configurar el document root del
   dominio/subdominio a `biblioteca-insectaria/public`.
4. Si Ferozo obliga a usar `public_html`, coloca el proyecto fuera de esa
   carpeta, copia **el contenido** de `public/` (incluido `.htaccess` y
   `build/`) a `public_html/` y ajusta las dos rutas de arranque en
   `public_html/index.php` para apuntar a `../biblioteca-insectaria/vendor/autoload.php`
   y `../biblioteca-insectaria/bootstrap/app.php`. Define
   `APP_PUBLIC_PATH=/ruta/absoluta/public_html` en `.env`; esto permite que
   Laravel ubique el manifiesto Vite y el service worker en el document root.
   Comprueba que `.env`, `vendor/` y `storage/` no sean accesibles por HTTP.
5. Configura `.env` con `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` con
   HTTPS, una `APP_KEY` propia, credenciales MySQL y SMTP. Fuerza HTTPS desde
   Ferozo, define `SESSION_SECURE_COOKIE=true` y verifica escritura en
   `storage/` y `bootstrap/cache/`.
6. Crea la cuenta inicial con `php artisan biblioteca:crear-voluntario` si
   Ferozo ofrece terminal. Si no ofrece terminal, ejecuta localmente
   `php artisan biblioteca:crear-voluntario --sql`; ingresa nombre, correo y
   contraseña (se solicita oculta), copia solo el `INSERT` generado y ejecútalo
   en phpMyAdmin sobre la base de producción. El comando genera un hash Bcrypt
   y no crea esa cuenta en tu base local. No guardes ni compartas la contraseña
   en un archivo ni expongas una ruta de registro temporal.
7. Para recordatorios, configura una tarea cron diaria a las 09:00 que ejecute
   `php /ruta/biblioteca-insectaria/artisan schedule:run`. Si el plan no permite
   cron, el panel conserva la lista de contactos para llamadas y los
   recordatorios automáticos no se enviarán.
8. Respaldar la base antes de cada actualización. Para desplegar cambios,
   compila primero `public/build/` en local y sube por FTP solo los archivos
   actualizados y el resultado compilado.

## Importación inicial desde Excel

Exporta la hoja de inventario a CSV UTF-8, preferiblemente con una fila por
título y estos encabezados:

```text
titulo,autor,isbn,editorial,anio,portada_url,mueble,cantidad
```

`titulo` y `autor` son obligatorios; `cantidad` es opcional y por defecto vale
1. Si un mismo libro aparece en varias filas, las cantidades se acumulan.
Los préstamos activos y socios deben migrarse por separado.

```powershell
php artisan biblioteca:importar-catalogo C:\ruta\inventario.csv
```

El comando admite CSV separado por coma o punto y coma, acumula existencias por
libro y escribe un informe CSV de filas rechazadas en
`storage/app/importacion-informe.csv`. Resuelve todas las filas del informe
antes de retirar el Excel/cuaderno original.

## Rutas útiles

- `GET /`: portal público de consulta.
- `GET /api/catalogo?q=...&pagina=1`: catálogo paginado.
- `GET /api/catalogo/{id}`: ficha pública de libro.
- `GET /login`: acceso voluntariado.
- `/admin`: panel y operaciones autenticadas.

La respuesta de catálogo solo publica datos bibliográficos, cantidades y
ubicación; nunca expone RUN, teléfonos, correos ni préstamos.
