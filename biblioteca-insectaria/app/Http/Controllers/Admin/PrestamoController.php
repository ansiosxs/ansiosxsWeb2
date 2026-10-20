<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Libro;
use App\Models\Prestamo;
use App\Models\Socio;
use App\Services\PrestamoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PrestamoController extends Controller
{
    public function index(): View
    {
        $prestamos = Prestamo::query()->with(['socio', 'libro', 'registradoPor'])
            ->where('estado', 'activo')->orderBy('fecha_vencimiento')->paginate(25);
        $socios = Socio::query()->where('activo', true)->orderBy('apellidos')->orderBy('nombres')->get();
        $contactar = Prestamo::query()->with(['socio', 'libro'])
            ->where('estado', 'activo')
            ->whereDate('fecha_vencimiento', today()->addDays((int) config('biblioteca.dias_recordatorio')))
            ->whereHas('socio', fn ($query) => $query->whereNull('email')->orWhere('email', ''))
            ->get();

        return view('admin.prestamos.index', [
            'prestamos' => $prestamos,
            'socios' => $socios,
            'contactar' => $contactar,
            'operacionUuid' => (string) Str::uuid(),
        ]);
    }

    public function buscarLibros(Request $request): JsonResponse
    {
        $datos = $request->validate(['q' => ['required', 'string', 'min:1', 'max:100']]);
        $busqueda = trim($datos['q']);
        $isbnBusqueda = preg_replace('/[\s.-]+/', '', $busqueda);

        $libros = Libro::query()
            ->withUnidadesPrestadas()
            ->where(fn ($query) => $query
                ->where('titulo', 'like', "%{$busqueda}%")
                ->orWhere('autor', 'like', "%{$busqueda}%")
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(isbn, '-', ''), ' ', ''), '.', '') LIKE ?", ["%{$isbnBusqueda}%"]))
            ->whereRaw('libros.cantidad_total > (SELECT COALESCE(SUM(p.cantidad - p.cantidad_devuelta), 0) FROM prestamos p WHERE p.libro_id = libros.id AND p.estado = ?)', ['activo'])
            ->limit(10)
            ->get();

        return response()->json($libros->map(fn (Libro $libro) => [
            'id' => $libro->id,
            'titulo' => $libro->titulo,
            'autor' => $libro->autor,
            'isbn' => $libro->isbn,
            'editorial' => $libro->editorial,
            'anio' => $libro->anio,
            'cantidad_disponible' => $libro->cantidad_disponible,
        ])->values());
    }

    public function store(Request $request, PrestamoService $service): RedirectResponse|JsonResponse
    {
        $datos = $request->validate([
            'libro_id' => ['required', 'integer', 'exists:libros,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000000'],
            'socio_id' => ['required', 'integer', 'exists:socios,id'],
            'operacion_uuid' => ['required', 'uuid'],
        ]);

        $prestamo = $service->prestar(
            (int) $datos['libro_id'],
            (int) $datos['cantidad'],
            (int) $datos['socio_id'],
            $request->user(),
            $datos['operacion_uuid'],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => 'Préstamo registrado correctamente.',
                'redireccion' => route('admin.prestamos.index'),
            ]);
        }

        return redirect()->route('admin.prestamos.index')->with('status', "Préstamo registrado. Vence el {$prestamo->fecha_vencimiento->format('d/m/Y')}.");
    }

    public function devolver(Request $request, Prestamo $prestamo, PrestamoService $service): RedirectResponse|JsonResponse
    {
        $datos = $request->validate(['cantidad' => ['required', 'integer', 'min:1', 'max:1000000']]);
        $resultado = $service->devolver($prestamo->id, (int) $datos['cantidad'], $request->user());

        $mensaje = $resultado['completo']
            ? 'Devolución registrada. El préstamo quedó completo.'
            : "Devolución registrada. Quedan {$resultado['cantidad_pendiente']} unidades pendientes.";

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => $mensaje, 'redireccion' => route('admin.prestamos.index')]);
        }

        if ($resultado['dias_atraso'] > 0) {
            $mensaje .= " El préstamo tenía {$resultado['dias_atraso']} días de atraso.";
        }

        return redirect()->route('admin.prestamos.index')->with('status', $mensaje);
    }

    public function morosidad(): View
    {
        $prestamos = Prestamo::query()
            ->with(['socio', 'libro', 'registradoPor'])
            ->where('estado', 'activo')
            ->whereDate('fecha_vencimiento', '<', today())
            ->orderBy('fecha_vencimiento')
            ->paginate(25);

        return view('admin.prestamos.morosidad', compact('prestamos'));
    }
}
