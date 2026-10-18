<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Libro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function index(): View
    {
        return view('catalogo.index', [
            'anuncios' => Anuncio::query()
                ->whereDate('publicar_desde', '<=', today())
                ->where(fn ($query) => $query->whereNull('publicar_hasta')->orWhereDate('publicar_hasta', '>=', today()))
                ->latest('publicar_desde')
                ->get(),
        ]);
    }

    public function buscar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'pagina' => ['nullable', 'integer', 'min:1'],
        ]);
        $busqueda = trim($validated['q'] ?? '');

        $libros = Libro::query()
            ->with('mueble:id,nombre')
            ->withUnidadesPrestadas()
            ->when($busqueda !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('titulo', 'like', "%{$busqueda}%")
                ->orWhere('autor', 'like', "%{$busqueda}%")))
            ->orderBy('titulo')
            ->paginate(12, ['*'], 'pagina', $validated['pagina'] ?? 1);

        return response()->json([
            'datos' => $libros->getCollection()->map(fn (Libro $libro) => [
                'id' => $libro->id,
                'titulo' => $libro->titulo,
                'autor' => $libro->autor,
                'portada_url' => $libro->portada_url,
                'mueble' => $libro->mueble?->nombre,
                'cantidad_total' => $libro->cantidad_total,
                'cantidad_disponible' => $libro->cantidad_disponible,
                'disponible' => $libro->cantidad_disponible > 0,
            ])->values(),
            'pagina' => $libros->currentPage(),
            'total_paginas' => $libros->lastPage(),
            'generado_en' => now()->toIso8601String(),
        ]);
    }

    public function mostrar(Libro $libro): JsonResponse
    {
        $libro->load('mueble:id,nombre');
        $libro->loadUnidadesPrestadas();

        return response()->json([
            'id' => $libro->id,
            'titulo' => $libro->titulo,
            'autor' => $libro->autor,
            'isbn' => $libro->isbn,
            'editorial' => $libro->editorial,
            'anio' => $libro->anio,
            'portada_url' => $libro->portada_url,
            'mueble' => $libro->mueble?->nombre,
            'cantidad_total' => $libro->cantidad_total,
            'cantidad_disponible' => $libro->cantidad_disponible,
            'disponible' => $libro->cantidad_disponible > 0,
        ]);
    }
}
