<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Libro;
use App\Models\Mueble;
use App\Models\Prestamo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function index(): View
    {
        $inventario = Libro::query()
            ->leftJoinSub(
                Prestamo::query()
                    ->select('libro_id')
                    ->selectRaw('SUM(cantidad - cantidad_devuelta) as unidades_prestadas')
                    ->where('estado', 'activo')
                    ->groupBy('libro_id'),
                'prestamos_activos',
                'prestamos_activos.libro_id',
                '=',
                'libros.id',
            )
            ->selectRaw('COUNT(libros.id) as titulos_catalogo')
            ->selectRaw('COALESCE(SUM(CASE WHEN libros.cantidad_total > COALESCE(prestamos_activos.unidades_prestadas, 0) THEN libros.cantidad_total - COALESCE(prestamos_activos.unidades_prestadas, 0) ELSE 0 END), 0) as unidades_disponibles')
            ->first();

        return view('catalogo.index', [
            'anuncios' => Anuncio::query()
                ->whereDate('publicar_desde', '<=', today())
                ->where(fn ($query) => $query->whereNull('publicar_hasta')->orWhereDate('publicar_hasta', '>=', today()))
                ->latest('publicar_desde')
                ->get(),
            'unidadesDisponibles' => (int) $inventario->unidades_disponibles,
            'titulosCatalogo' => (int) $inventario->titulos_catalogo,
            'muebles' => Mueble::query()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function buscar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'pagina' => ['nullable', 'integer', 'min:1'],
            'mueble_id' => ['nullable', 'integer', 'min:1', 'exists:muebles,id'],
            'orden' => ['nullable', 'string', Rule::in(['titulo_asc', 'titulo_desc', 'autor_asc', 'autor_desc'])],
        ]);
        $busqueda = trim($validated['q'] ?? '');
        $isbnBusqueda = preg_replace('/[\s.-]+/', '', $busqueda);
        [$columna, $direccion] = match ($validated['orden'] ?? 'autor_asc') {
            'titulo_desc' => ['titulo', 'desc'],
            'autor_asc' => ['autor', 'asc'],
            'autor_desc' => ['autor', 'desc'],
            'titulo_asc' => ['titulo', 'asc'],
            default => ['autor', 'asc'],
        };

        $libros = Libro::query()
            ->with('mueble:id,nombre')
            ->withUnidadesPrestadas()
            ->when($busqueda !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('titulo', 'like', "%{$busqueda}%")
                ->orWhere('autor', 'like', "%{$busqueda}%")
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(isbn, '-', ''), ' ', ''), '.', '') LIKE ?", ["%{$isbnBusqueda}%"])))
            ->when(isset($validated['mueble_id']), fn ($query) => $query->where('mueble_id', $validated['mueble_id']))
            ->when($columna === 'autor', fn ($query) => $query->orderByRaw('CASE WHEN autor IS NULL THEN 1 ELSE 0 END'))
            ->orderBy($columna, $direccion)
            ->orderBy('id')
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
