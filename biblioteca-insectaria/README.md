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
php artisan storage:link
php artisan biblioteca:crear-voluntario
npm run build
php artisan serve --port=8001
```

Las migraciones más recientes consolidan los ejemplares en una cantidad por
libro y permiten crear cuentas pendientes de configuración; aplica
`php artisan migrate --force` al actualizar una instalación existente.

Abre `http://127.0.0.1:8001/login` e ingresa con el nombre o correo y la
contraseña de la cuenta creada. Las pruebas automatizadas usan SQLite en memoria:

```powershell
php artisan test
```

El comando para crear voluntarios solicita una contraseña provisional de forma
oculta y exige al menos 12 caracteres; como en el panel, la persona registra
su correo y contraseña definitiva al ingresar por primera vez. No hay registro
público de cuentas. También se puede crear el primer usuario con el seeder si
se establecen temporalmente
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
- Alta de voluntarios con nombre y contraseña provisional; en el primer ingreso
  deben registrar su correo y elegir una contraseña definitiva antes de usar el
  panel.
- Rol Editor con las funciones de voluntariado y gestión de noticias; los
  administradores también pueden crear, editar y eliminar noticias.
- Noticias con imagen opcional y editor visual HTML seguro en sus textos y en
  los anuncios (negrita, cursiva, encabezados, citas, enlaces, listas,
  deshacer/rehacer y pegar con formato). En una cita, Enter vuelve al párrafo
  normal y Ctrl+Enter inserta un salto de línea. La API pública de solo lectura
  de noticias está disponible para `../web-estatica/noticias.html`; cada
  noticia también tiene una página HTML pública con metadatos Open Graph,
  Twitter y Schema.org para compartir e indexar el artículo.
- Administración de enlaces públicos para `../web-estatica/enlaces.html`.
  Editores y administradores pueden crear, ordenar, activar, ocultar y eliminar
  enlaces; la API pública solo devuelve los activos y acepta direcciones HTTP
  y HTTPS. Incluye enlaces iniciales organizados por sección, visibles como
  pendientes hasta que se les agregue una dirección.
- CRUD de libros con inventario por cantidades, estanterías y anuncios.
- Socios con RUN chileno validado/normalizado, teléfono celular chileno
  normalizado, inscripción y renovación anual.
- Préstamos transaccionales por libro y cantidad, con `lockForUpdate`, plazo
  configurable, límites de unidades por socio, morosidad y UUID idempotente.
- Búsqueda de libros por título, autor o ISBN; los formularios de préstamos y
  alta/edición de libros pueden escanear códigos ISBN-13 con la cámara del
  dispositivo. En móviles, la cámara requiere HTTPS y permiso del navegador
  (localhost también funciona).
- Devoluciones parciales por préstamo, días de atraso, lista de morosidad, lista telefónica y
  recordatorios de correo registrados para no repetirlos.
- Portal público Vue, API de catálogo sin información de socios, búsqueda
  paginada y caché de la última consulta. Los anuncios vigentes se muestran
  en el resumen administrativo y como avisos flotantes cerrables, no en el
  catálogo público.
  para uso sin conexión.
- Borradores locales y reintento visible ante pérdida de red o errores 5xx en
  los formularios de préstamo/devolución. Las escrituras no se almacenan en el
  service worker ni se envían en segundo plano.
- Resumen estadístico agregado sin exponer fichas individuales de socios.
- Turnos mensuales con una lista administrable de voluntarios habilitados,
  participación por mes, días preferidos de lunes a viernes y cantidad de
  turnos mensual (2 por defecto). La propuesta asigna esa cantidad de días por
  participante; el borrador y sus asignaciones se crean automáticamente el
  último día del mes para el mes siguiente, copiando la participación anterior.
- Actividades con notas de convocatoria y reportes privados de voluntarios
  que administración puede marcar como pendientes, en proceso o resueltos.

## Variables principales

| Variable | Uso |
| --- | --- |
| `APP_ENV`, `APP_DEBUG`, `APP_KEY`, `APP_URL` | Entorno, modo depuración, clave generada y URL HTTPS. |
| `FRONTEND_URL` | Origen del sitio estático y autorizado para consultar el API público; por defecto `http://127.0.0.1:8002` en local y `https://www.ansiosxs.cl` en otros entornos. |
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
   Define `APP_URL=https://biblioteca.ansiosxs.cl` y
   `FRONTEND_URL=https://www.ansiosxs.cl` para la API de noticias. Ejecuta
   `php artisan storage:link` para publicar las imágenes subidas desde el panel.
6. Crea la cuenta inicial con `php artisan biblioteca:crear-voluntario` si
   Ferozo ofrece terminal. Si no ofrece terminal, ejecuta localmente
   `php artisan biblioteca:crear-voluntario --sql`; ingresa nombre, correo y
   contraseña (se solicita oculta), copia solo el `INSERT` generado y ejecútalo
   en phpMyAdmin sobre la base de producción. El comando genera un hash Bcrypt
   y no crea esa cuenta en tu base local. No guardes ni compartas la contraseña
   en un archivo ni expongas una ruta de registro temporal.
7. Configura una tarea cron cada minuto que ejecute
   `php /ruta/biblioteca-insectaria/artisan schedule:run`. Laravel ejecutará los
   recordatorios diarios a las 09:00 y creará el borrador de turnos siguiente
   el último día de cada mes a las 23:50. El comando de turnos también puede
   ejecutarse manualmente con `php artisan turnos:crear-borrador-siguiente`.
   Si el plan no permite cron, los procesos programados no se ejecutarán.
8. Respaldar la base antes de cada actualización. Para desplegar cambios,
   compila primero `public/build/` en local y sube por FTP solo los archivos
   actualizados y el resultado compilado.

## Importación inicial desde Excel

Exporta la hoja de inventario a CSV UTF-8, preferiblemente con una fila por
título y estos encabezados:

```text
titulo,autor,isbn,editorial,anio,portada_url,mueble,cantidad
```

También se importan `paginas`, `idioma` y `descripcion` (en el CSV del
inventario, `Año de publicación`, `Páginas` y `Descripción`). Estos datos y el
año de publicación se muestran en la ficha individual del libro, no en las
listas del catálogo.

`titulo` y `autor` son obligatorios; `cantidad` es opcional y por defecto vale
1. Si un mismo libro aparece en varias filas, las cantidades se acumulan.
Los préstamos activos y socios deben migrarse por separado.

```powershell
php artisan biblioteca:importar-catalogo C:\ruta\inventario.csv
```

También puedes usar el lanzador Python desde la carpeta del proyecto. Sin
argumentos, toma `book_inventory_ -.csv` desde la carpeta padre:

```powershell
python scripts/importar_libros.py
```

Para elegir otro CSV o asignar el inventario a una estantería:

```powershell
python scripts/importar_libros.py "C:\ruta\inventario.csv" --mueble="Plan Lector"
```

El lanzador usa el comando Artisan y la conexión de base de datos de Laravel;
no requiere instalar paquetes de Python ni poner credenciales en el script.
Ejecuta antes las migraciones pendientes y respalda la base de datos.

El comando admite CSV separado por coma o punto y coma, acumula existencias por
libro y escribe un informe CSV de filas rechazadas en
`storage/app/importacion-informe.csv`. También reconoce los encabezados del
inventario `ID,ISBN,Título,Autor,Editorial,Cantidad,URL Imagen,Año de
publicación,Páginas,Idioma,Descripción`; las filas sin título o autor quedan
en el informe porque esos campos son obligatorios en la base de datos. Para
importar ese inventario y asignar todos los libros
existentes e importados al mueble `Plan Lector`, ejecuta desde esta carpeta:

```powershell
python scripts/importar_libros.py "..\book_inventory_ -.csv" --mueble="Plan Lector"
```

El comando acumula cantidades: ejecútalo una sola vez para cada inventario, o
revisa las cantidades antes de volver a importarlo. Resuelve todas las filas
del informe antes de retirar el Excel/cuaderno original.

## Rutas útiles

- `GET /`: portal público de consulta.
- `GET /api/catalogo?q=...&pagina=1`: catálogo paginado.
- `GET /api/catalogo/{id}`: ficha pública de libro.
- `GET https://biblioteca.ansiosxs.cl/api/noticias`: noticias públicas paginadas.
- `GET https://biblioteca.ansiosxs.cl/api/noticias/{id}`: noticia pública.
- `GET https://biblioteca.ansiosxs.cl/api/enlaces`: enlaces públicos activos, ordenados manualmente.
- `GET /login`: acceso voluntariado.
- `/admin`: panel y operaciones autenticadas.

La respuesta de catálogo solo publica datos bibliográficos, cantidades y
ubicación; nunca expone RUN, teléfonos, correos ni préstamos.
