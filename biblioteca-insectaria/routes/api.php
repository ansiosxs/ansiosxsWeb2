<?php

use App\Http\Controllers\EnlacesPublicosController;
use App\Http\Controllers\NoticiaPublicaController;
use Illuminate\Support\Facades\Route;

Route::get('noticias', [NoticiaPublicaController::class, 'index'])->name('api.noticias.index');
Route::get('noticias/{noticia}', [NoticiaPublicaController::class, 'show'])
    ->whereNumber('noticia')
    ->name('api.noticias.show');
Route::get('enlaces', [EnlacesPublicosController::class, 'index'])->name('api.enlaces.index');
