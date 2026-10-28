<?php

use App\Http\Controllers\Admin\AnuncioController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LibroController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\MuebleController;
use App\Http\Controllers\Admin\PrestamoController;
use App\Http\Controllers\Admin\SocioController;
use App\Http\Controllers\Admin\TurnoController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VolunteerReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogoController::class, 'index'])->name('catalogo');
Route::get('/api/catalogo', [CatalogoController::class, 'buscar'])->name('api.catalogo.index');
Route::get('/api/catalogo/{libro}', [CatalogoController::class, 'mostrar'])->whereNumber('libro')->name('api.catalogo.show');

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('guest')->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->middleware('guest')->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('guest')->name('password.update');

Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthController::class, 'store'])->middleware('log.user.action')->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware(['auth', 'log.user.action'])->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'log.user.action'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('libros', LibroController::class)->except(['destroy']);
    Route::delete('libros/{libro}', [LibroController::class, 'destroy'])->middleware('role:admin')->name('libros.destroy');
    Route::resource('socios', SocioController::class)->except(['destroy']);
    Route::delete('socios/{socio}', [SocioController::class, 'destroy'])->middleware('role:admin')->name('socios.destroy');
    Route::post('socios/{socio}/renovar', [SocioController::class, 'renovar'])->name('socios.renovar');
    Route::post('socios/{socio}/caducar', [SocioController::class, 'caducar'])->name('socios.caducar');
    Route::put('socios/{socio}/caducidad', [SocioController::class, 'cambiarCaducidad'])->name('socios.caducidad');

    Route::get('prestamos', [PrestamoController::class, 'index'])->name('prestamos.index');
    Route::get('prestamos/libros', [PrestamoController::class, 'buscarLibros'])->name('prestamos.libros');
    Route::post('prestamos', [PrestamoController::class, 'store'])->name('prestamos.store');
    Route::post('prestamos/{prestamo}/devolver', [PrestamoController::class, 'devolver'])->name('prestamos.devolver');
    Route::get('morosidad', [PrestamoController::class, 'morosidad'])->name('morosidad');

    Route::get('estanterias', [MuebleController::class, 'index'])->name('estanterias.index');
    Route::get('muebles', [MuebleController::class, 'index'])->name('muebles.index');
    Route::get('anuncios', [AnuncioController::class, 'index'])->name('anuncios.index');
    Route::get('estadisticas', DashboardController::class)->name('estadisticas');
    Route::get('estadisticas/libros-mas-prestados', [DashboardController::class, 'librosMasPrestados'])->name('estadisticas.libros-mas-prestados');
    Route::get('turnos', [TurnoController::class, 'index'])->name('turnos.index');
    Route::get('turnos/{turno}', [TurnoController::class, 'show'])->whereNumber('turno')->name('turnos.show');
    Route::post('turnos/{turno}/disponibilidad', [TurnoController::class, 'disponibilidad'])->whereNumber('turno')->name('turnos.disponibilidad');
    Route::get('turnos/{turno}/calendario.svg', [TurnoController::class, 'calendarioSvg'])->whereNumber('turno')->name('turnos.calendario');
    Route::get('reportes', [VolunteerReportController::class, 'index'])->name('reportes.index');
    Route::post('reportes', [VolunteerReportController::class, 'store'])->name('reportes.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('usuarios', UserController::class)->except(['show']);
    Route::post('usuarios/{usuario}/estado', [UserController::class, 'toggleStatus'])->name('usuarios.toggle-status');
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('turnos/crear', [TurnoController::class, 'create'])->name('turnos.create');
    Route::post('turnos', [TurnoController::class, 'store'])->name('turnos.store');
    Route::put('turnos/{turno}/dias/{dia}', [TurnoController::class, 'actualizarDia'])->whereNumber(['turno', 'dia'])->name('turnos.dias.update');
    Route::post('turnos/{turno}/generar', [TurnoController::class, 'generar'])->whereNumber('turno')->name('turnos.generar');
    Route::post('turnos/{turno}/publicar', [TurnoController::class, 'publicar'])->whereNumber('turno')->name('turnos.publicar');
    Route::post('turnos/{turno}/actividades', [TurnoController::class, 'guardarActividad'])->whereNumber('turno')->name('turnos.actividades.store');
    Route::put('turnos/{turno}/actividades/{actividad}', [TurnoController::class, 'actualizarActividad'])->whereNumber(['turno', 'actividad'])->name('turnos.actividades.update');
    Route::delete('turnos/{turno}/actividades/{actividad}', [TurnoController::class, 'eliminarActividad'])->whereNumber(['turno', 'actividad'])->name('turnos.actividades.destroy');
    Route::put('reportes/{reporte}/estado', [VolunteerReportController::class, 'actualizarEstado'])->whereNumber('reporte')->name('reportes.estado');
    Route::get('exportar/libros', [\App\Http\Controllers\Admin\ExportController::class, 'libros'])->name('exportar.libros');
    Route::get('exportar/socios', [\App\Http\Controllers\Admin\ExportController::class, 'socios'])->name('exportar.socios');
    Route::get('exportar/prestamos', [\App\Http\Controllers\Admin\ExportController::class, 'prestamos'])->name('exportar.prestamos');
    Route::get('exportar/todo', [\App\Http\Controllers\Admin\ExportController::class, 'todo'])->name('exportar.todo');

    Route::resource('anuncios', AnuncioController::class)->except(['index', 'show']);

    Route::post('estanterias', [MuebleController::class, 'store'])->name('estanterias.store');
    Route::put('estanterias/{mueble}', [MuebleController::class, 'update'])->name('estanterias.update');
    Route::delete('estanterias/{mueble}', [MuebleController::class, 'destroy'])->name('estanterias.destroy');
    Route::post('muebles', [MuebleController::class, 'store'])->name('muebles.store');
    Route::put('muebles/{mueble}', [MuebleController::class, 'update'])->name('muebles.update');
    Route::delete('muebles/{mueble}', [MuebleController::class, 'destroy'])->name('muebles.destroy');
});
