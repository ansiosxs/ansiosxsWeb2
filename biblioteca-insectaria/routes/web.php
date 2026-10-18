<?php

use App\Http\Controllers\Admin\AnuncioController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LibroController;
use App\Http\Controllers\Admin\MuebleController;
use App\Http\Controllers\Admin\PrestamoController;
use App\Http\Controllers\Admin\SocioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogoController::class, 'index'])->name('catalogo');
Route::get('/api/catalogo', [CatalogoController::class, 'buscar'])->name('api.catalogo.index');
Route::get('/api/catalogo/{libro}', [CatalogoController::class, 'mostrar'])->whereNumber('libro')->name('api.catalogo.show');

Route::get('/login', [AuthController::class, 'create'])->name('login');

Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('libros', LibroController::class);
    Route::resource('socios', SocioController::class);
    Route::post('socios/{socio}/renovar', [SocioController::class, 'renovar'])->name('socios.renovar');
    Route::post('socios/{socio}/caducar', [SocioController::class, 'caducar'])->name('socios.caducar');
    Route::put('socios/{socio}/caducidad', [SocioController::class, 'cambiarCaducidad'])->name('socios.caducidad');

    Route::get('prestamos', [PrestamoController::class, 'index'])->name('prestamos.index');
    Route::get('prestamos/libros', [PrestamoController::class, 'buscarLibros'])->name('prestamos.libros');
    Route::post('prestamos', [PrestamoController::class, 'store'])->name('prestamos.store');
    Route::post('prestamos/{prestamo}/devolver', [PrestamoController::class, 'devolver'])->name('prestamos.devolver');
    Route::get('morosidad', [PrestamoController::class, 'morosidad'])->name('morosidad');

    Route::resource('anuncios', AnuncioController::class)->except(['show']);
    Route::get('estanterias', [MuebleController::class, 'index'])->name('estanterias.index');
    Route::post('estanterias', [MuebleController::class, 'store'])->name('estanterias.store');
    Route::put('estanterias/{mueble}', [MuebleController::class, 'update'])->name('estanterias.update');
    Route::delete('estanterias/{mueble}', [MuebleController::class, 'destroy'])->name('estanterias.destroy');
    Route::get('muebles', [MuebleController::class, 'index'])->name('muebles.index');
    Route::post('muebles', [MuebleController::class, 'store'])->name('muebles.store');
    Route::put('muebles/{mueble}', [MuebleController::class, 'update'])->name('muebles.update');
    Route::delete('muebles/{mueble}', [MuebleController::class, 'destroy'])->name('muebles.destroy');
    Route::get('estadisticas', DashboardController::class)->name('estadisticas');
});
