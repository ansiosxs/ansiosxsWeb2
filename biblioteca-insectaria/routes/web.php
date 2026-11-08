<?php

use App\Http\Controllers\Admin\AnuncioController;
use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnlacePublicoController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\LibroController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\MuebleController;
use App\Http\Controllers\Admin\NoticiaController;
use App\Http\Controllers\Admin\PrestamoController;
use App\Http\Controllers\Admin\RecordatorioController;
use App\Http\Controllers\Admin\SocioController;
use App\Http\Controllers\Admin\TurnoController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VolunteerReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\NoticiaPublicaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogoController::class, 'index'])->name('catalogo');
Route::get('/noticias/{noticia}', [NoticiaPublicaController::class, 'pagina'])
    ->whereNumber('noticia')
    ->name('noticias.publicas.show');
Route::get('/api/catalogo', [CatalogoController::class, 'buscar'])->name('api.catalogo.index');
Route::get('/api/catalogo/{libro}', [CatalogoController::class, 'mostrar'])->whereNumber('libro')->name('api.catalogo.show');
Route::get('/prestamos/{prestamo}/postergar', [PrestamoController::class, 'confirmarPostergacion'])
    ->middleware('signed')->whereNumber('prestamo')->name('prestamos.postergar.confirmar');
Route::post('/prestamos/{prestamo}/postergar', [PrestamoController::class, 'postergar'])
    ->middleware('signed')->whereNumber('prestamo')->name('prestamos.postergar');

Route::get('/_diag', fn () => [
    'base_path' => base_path(),
    'web_md5' => md5_file(base_path('routes/web.php')),
    'web_modificado' => date('c', filemtime(base_path('routes/web.php'))),
    'rutas_cacheadas' => app()->routesAreCached(),
    'laravel' => app()->version(),
    'libros' => collect(Route::getRoutes())
        ->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri().' → '.$route->getName())
        ->filter(fn ($route) => str_contains($route, 'libros'))
        ->values(),
])
    ->middleware(['auth', 'role:admin']);

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('guest')->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->middleware('guest')->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('guest')->name('password.update');
Route::middleware('auth')->group(function () {
    Route::get('/completar-cuenta', [AuthController::class, 'showAccountSetup'])->name('account.setup');
    Route::post('/completar-cuenta', [AuthController::class, 'completeAccountSetup'])->name('account.setup.store');
});

Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthController::class, 'store'])->middleware('log.user.action')->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware(['auth', 'log.user.action'])->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'account.configured', 'log.user.action'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('libros', [LibroController::class, 'index'])->name('libros.index');
    Route::get('libros/create', [LibroController::class, 'create'])->name('libros.create');
    Route::post('libros', [LibroController::class, 'store'])->name('libros.store');
    Route::get('libros/{libro}', [LibroController::class, 'show'])->name('libros.show');
    Route::get('libros/{libro}/edit', [LibroController::class, 'edit'])->name('libros.edit');
    Route::match(['put', 'patch'], 'libros/{libro}', [LibroController::class, 'update'])->name('libros.update');
    Route::delete('libros/{libro}', [LibroController::class, 'destroy'])->middleware('role:admin')->name('libros.destroy');
    Route::get('socios', [SocioController::class, 'index'])->name('socios.index');
    Route::get('socios/create', [SocioController::class, 'create'])->name('socios.create');
    Route::post('socios', [SocioController::class, 'store'])->name('socios.store');
    Route::get('socios/{socio}', [SocioController::class, 'show'])->name('socios.show');
    Route::get('socios/{socio}/edit', [SocioController::class, 'edit'])->name('socios.edit');
    Route::match(['put', 'patch'], 'socios/{socio}', [SocioController::class, 'update'])->name('socios.update');
    Route::delete('socios/{socio}', [SocioController::class, 'destroy'])->middleware('role:admin')->name('socios.destroy');
    Route::post('socios/{socio}/renovar', [SocioController::class, 'renovar'])->name('socios.renovar');
    Route::post('socios/{socio}/caducar', [SocioController::class, 'caducar'])->name('socios.caducar');
    Route::put('socios/{socio}/caducidad', [SocioController::class, 'cambiarCaducidad'])->name('socios.caducidad');

    Route::get('prestamos', [PrestamoController::class, 'index'])->name('prestamos.index');
    Route::get('prestamos/libros', [PrestamoController::class, 'buscarLibros'])->name('prestamos.libros');
    Route::post('prestamos', [PrestamoController::class, 'store'])->name('prestamos.store');
    Route::post('prestamos/{prestamo}/devolver', [PrestamoController::class, 'devolver'])->name('prestamos.devolver');
    Route::get('prestamos/{prestamo}/editar', [PrestamoController::class, 'editar'])->name('prestamos.editar');
    Route::put('prestamos/{prestamo}', [PrestamoController::class, 'update'])->name('prestamos.update');
    Route::delete('prestamos/{prestamo}', [PrestamoController::class, 'destroy'])->name('prestamos.destroy');
    Route::get('morosidad', [PrestamoController::class, 'morosidad'])->name('morosidad');

    Route::get('estanterias', [MuebleController::class, 'index'])->name('estanterias.index');
    Route::get('muebles', [MuebleController::class, 'index'])->name('muebles.index');
    Route::get('anuncios', [AnuncioController::class, 'index'])->name('anuncios.index');
    Route::get('estadisticas', DashboardController::class)->name('estadisticas');
    Route::get('estadisticas/libros-mas-prestados', [DashboardController::class, 'librosMasPrestados'])->name('estadisticas.libros-mas-prestados');
    Route::get('turnos', [TurnoController::class, 'index'])->name('turnos.index');
    Route::get('turnos/{turno}', [TurnoController::class, 'show'])->whereNumber('turno')->name('turnos.show');
    Route::get('turnos/{turno}/calendario.svg', [TurnoController::class, 'calendarioSvg'])->whereNumber('turno')->name('turnos.calendario');
    Route::get('reportes', [VolunteerReportController::class, 'index'])->name('reportes.index');
    Route::post('reportes', [VolunteerReportController::class, 'store'])->name('reportes.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'account.configured', 'role:admin', 'log.user.action'])->group(function () {
    Route::get('configuracion', [ConfiguracionController::class, 'edit'])->name('configuracion.edit');
    Route::put('configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
    Route::get('recordatorios', [RecordatorioController::class, 'index'])->name('recordatorios.index');
    Route::put('recordatorios', [RecordatorioController::class, 'update'])->name('recordatorios.update');
    Route::get('recordatorios/plantillas', [RecordatorioController::class, 'plantillas'])->name('recordatorios.plantillas');
    Route::put('recordatorios/plantillas', [RecordatorioController::class, 'actualizarPlantillas'])->name('recordatorios.plantillas.update');
    Route::post('recordatorios/{recordatorio}/enviar', [RecordatorioController::class, 'enviar'])->name('recordatorios.enviar');
    Route::post('recordatorios/{recordatorio}/descartar', [RecordatorioController::class, 'descartar'])->name('recordatorios.descartar');
    Route::post('prestamos/{prestamo}/correo-morosidad', [PrestamoController::class, 'correoMorosidad'])->name('prestamos.correo-morosidad');
    Route::post('prestamos/{prestamo}/marcar-devuelto', [PrestamoController::class, 'marcarComoDevuelto'])->name('prestamos.marcar-devuelto');
    Route::get('usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::get('usuarios/create', [UserController::class, 'create'])->name('usuarios.create');
    Route::post('usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::get('usuarios/{usuario}/edit', [UserController::class, 'edit'])->name('usuarios.edit');
    Route::match(['put', 'patch'], 'usuarios/{usuario}', [UserController::class, 'update'])->name('usuarios.update');
    Route::delete('usuarios/{usuario}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    Route::post('usuarios/{usuario}/estado', [UserController::class, 'toggleStatus'])->name('usuarios.toggle-status');
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('turnos/crear', [TurnoController::class, 'create'])->name('turnos.create');
    Route::post('turnos', [TurnoController::class, 'store'])->name('turnos.store');
    Route::put('turnos/{turno}/dias/{dia}', [TurnoController::class, 'actualizarDia'])->whereNumber(['turno', 'dia'])->name('turnos.dias.update');
    Route::put('turnos/{turno}/voluntarios', [TurnoController::class, 'actualizarVoluntarios'])->whereNumber('turno')->name('turnos.voluntarios.update');
    Route::post('turnos/{turno}/generar', [TurnoController::class, 'generar'])->whereNumber('turno')->name('turnos.generar');
    Route::post('turnos/{turno}/publicar', [TurnoController::class, 'publicar'])->whereNumber('turno')->name('turnos.publicar');
    Route::post('turnos/{turno}/actividades', [TurnoController::class, 'guardarActividad'])->whereNumber('turno')->name('turnos.actividades.store');
    Route::put('turnos/{turno}/actividades/{actividad}', [TurnoController::class, 'actualizarActividad'])->whereNumber(['turno', 'actividad'])->name('turnos.actividades.update');
    Route::delete('turnos/{turno}/actividades/{actividad}', [TurnoController::class, 'eliminarActividad'])->whereNumber(['turno', 'actividad'])->name('turnos.actividades.destroy');
    Route::put('reportes/{reporte}/estado', [VolunteerReportController::class, 'actualizarEstado'])->whereNumber('reporte')->name('reportes.estado');
    Route::get('exportar/libros', [ExportController::class, 'libros'])->name('exportar.libros');
    Route::get('exportar/socios', [ExportController::class, 'socios'])->name('exportar.socios');
    Route::get('exportar/prestamos', [ExportController::class, 'prestamos'])->name('exportar.prestamos');
    Route::get('exportar/todo', [ExportController::class, 'todo'])->name('exportar.todo');

    Route::get('anuncios/create', [AnuncioController::class, 'create'])->name('anuncios.create');
    Route::post('anuncios', [AnuncioController::class, 'store'])->name('anuncios.store');
    Route::get('anuncios/{anuncio}/edit', [AnuncioController::class, 'edit'])->name('anuncios.edit');
    Route::match(['put', 'patch'], 'anuncios/{anuncio}', [AnuncioController::class, 'update'])->name('anuncios.update');
    Route::delete('anuncios/{anuncio}', [AnuncioController::class, 'destroy'])->name('anuncios.destroy');

    Route::post('estanterias', [MuebleController::class, 'store'])->name('estanterias.store');
    Route::put('estanterias/{mueble}', [MuebleController::class, 'update'])->name('estanterias.update');
    Route::delete('estanterias/{mueble}', [MuebleController::class, 'destroy'])->name('estanterias.destroy');
    Route::post('muebles', [MuebleController::class, 'store'])->name('muebles.store');
    Route::put('muebles/{mueble}', [MuebleController::class, 'update'])->name('muebles.update');
    Route::delete('muebles/{mueble}', [MuebleController::class, 'destroy'])->name('muebles.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'account.configured', 'role:admin,editor', 'log.user.action'])->group(function () {
    Route::get('noticias', [NoticiaController::class, 'index'])->name('noticias.index');
    Route::get('noticias/create', [NoticiaController::class, 'create'])->name('noticias.create');
    Route::post('noticias', [NoticiaController::class, 'store'])->name('noticias.store');
    Route::get('noticias/{noticia}/edit', [NoticiaController::class, 'edit'])->name('noticias.edit');
    Route::match(['put', 'patch'], 'noticias/{noticia}', [NoticiaController::class, 'update'])->name('noticias.update');
    Route::delete('noticias/{noticia}', [NoticiaController::class, 'destroy'])->name('noticias.destroy');

    Route::get('enlaces', [EnlacePublicoController::class, 'index'])->name('enlaces.index');
    Route::get('enlaces/create', [EnlacePublicoController::class, 'create'])->name('enlaces.create');
    Route::post('enlaces', [EnlacePublicoController::class, 'store'])->name('enlaces.store');
    Route::get('enlaces/{enlace}/edit', [EnlacePublicoController::class, 'edit'])->name('enlaces.edit');
    Route::match(['put', 'patch'], 'enlaces/{enlace}', [EnlacePublicoController::class, 'update'])->name('enlaces.update');
    Route::delete('enlaces/{enlace}', [EnlacePublicoController::class, 'destroy'])->name('enlaces.destroy');
});
