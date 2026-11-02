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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrestamoController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $datos = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = trim($datos['q'] ?? '');
        $terminos = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);

        $prestamos = Prestamo::query()->with(['socio', 'libro', 'registradoPor'])
            ->where('estado', 'activo');

        if ($q !== '') {
            $prestamos->where(fn ($search) => $search
                ->whereHas('libro', fn ($libros) => $libros->where('titulo', 'like', "%{$q}%"))
                ->orWhereHas('socio', fn ($socios) => $socios->where(function ($names) use ($terminos) {
                    foreach ($terminos as $termino) {
                        $names->where(function ($name) use ($termino) {
                            $name->where('nombres', 'like', "%{$termino}%")
                                ->orWhere('apellidos', 'like', "%{$termino}%");
                        });
                    }
                })));
        }

        $prestamos = $prestamos->orderBy('fecha_vencimiento')->paginate(25)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.prestamos._filas', ['prestamos' => $prestamos, 'q' => $q])->render(),
                'pagination' => view('admin.prestamos._paginacion', compact('prestamos'))->render(),
            ]);
        }

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
            'q' => $q,
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
        $mensaje = "Préstamo ingresado correctamente. Vence el {$prestamo->fecha_vencimiento->format('d/m/Y')}.";

        if ($request->expectsJson()) {
            $request->session()->flash('status', $mensaje);

            return response()->json([
                'mensaje' => $mensaje,
                'redireccion' => route('admin.prestamos.index'),
            ]);
        }

        return redirect()->route('admin.prestamos.index')->with('status', $mensaje);
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

    public function correoMorosidad(Prestamo $prestamo): RedirectResponse
    {
        $prestamo->load(['socio', 'libro']);
        $this->validarPrestamoVencido($prestamo);

        $email = trim((string) $prestamo->socio?->email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('admin.morosidad')
                ->withErrors(['email' => 'El socio no tiene un correo válido. Corrige su ficha antes de contactarlo.']);
        }

        $nombre = trim(($prestamo->socio->nombres ?? '').' '.($prestamo->socio->apellidos ?? ''));
        $asunto = 'Aviso por préstamo vencido - Biblioteca La Insectaria';
        $mensaje = "Hola {$nombre},\r\n\r\n"
            ."Te escribimos para recordarte que tienes un préstamo vencido en nuestra biblioteca.\r\n\r\n"
            ."Libro: {$prestamo->libro->titulo}\r\n"
            ."Unidades pendientes: {$prestamo->cantidad_pendiente}\r\n"
            ."Fecha de vencimiento: {$prestamo->fecha_vencimiento->format('d/m/Y')}\r\n\r\n"
            .'Si crees que es un error, responde este correo.';
        $mailto = 'mailto:'.$email.'?'.http_build_query([
            'subject' => $asunto,
            'body' => $mensaje,
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away($mailto);
    }

    public function marcarComoDevuelto(Request $request, Prestamo $prestamo, PrestamoService $service): RedirectResponse
    {
        $this->validarPrestamoVencido($prestamo);
        $service->devolver($prestamo->id, $prestamo->cantidad_pendiente, $request->user());

        return redirect()->route('admin.morosidad')->with('status', 'El préstamo fue marcado como devuelto.');
    }

    public function editar(Request $request, Prestamo $prestamo): View
    {
        $datos = $request->validate([
            'volver' => ['nullable', 'in:prestamos'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $prestamo->load(['libro', 'socio']);

        return view('admin.prestamos.formulario-edicion', [
            'prestamo' => $prestamo,
            'volver' => $datos['volver'] ?? null,
            'q' => $datos['q'] ?? '',
        ]);
    }

    public function update(Request $request, Prestamo $prestamo): RedirectResponse
    {
        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000000'],
            'cantidad_devuelta' => ['required', 'integer', 'min:0'],
            'fecha_prestamo' => ['required', 'date'],
            'fecha_vencimiento' => ['required', 'date'],
            'volver' => ['nullable', 'in:prestamos'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        if ($datos['cantidad_devuelta'] > $datos['cantidad']) {
            throw ValidationException::withMessages([
                'cantidad_devuelta' => 'La cantidad devuelta no puede ser mayor a la cantidad prestada.',
            ]);
        }

        $completo = $datos['cantidad_devuelta'] >= $datos['cantidad'];

        $prestamo->update([
            'cantidad' => $datos['cantidad'],
            'cantidad_devuelta' => $datos['cantidad_devuelta'],
            'fecha_prestamo' => $datos['fecha_prestamo'],
            'fecha_vencimiento' => $datos['fecha_vencimiento'],
            'estado' => $completo ? 'devuelto' : 'activo',
            'fecha_devolucion' => $completo ? ($prestamo->fecha_devolucion ?? now()) : null,
        ]);

        $redireccion = ($datos['volver'] ?? null) === 'prestamos'
            ? redirect()->route('admin.prestamos.index', ['q' => $datos['q'] ?? ''])
            : redirect()->route('admin.socios.show', $prestamo->socio_id);

        return $redireccion->with('status', 'El préstamo fue actualizado correctamente.');
    }

    public function destroy(Request $request, Prestamo $prestamo): RedirectResponse
    {
        $datos = $request->validate([
            'volver' => ['nullable', 'in:prestamos,socio'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $socioId = $prestamo->socio_id;
        $prestamo->delete();

        $redireccion = ($datos['volver'] ?? null) === 'socio'
            ? redirect()->route('admin.socios.show', $socioId)
            : redirect()->route('admin.prestamos.index', array_filter(['q' => $datos['q'] ?? null]));

        return $redireccion->with('status', 'El registro del préstamo fue eliminado.');
    }

    private function validarPrestamoVencido(Prestamo $prestamo): void
    {
        if ($prestamo->estado !== 'activo' || ! $prestamo->fecha_vencimiento->lt(today())) {
            throw ValidationException::withMessages([
                'prestamo' => 'Este préstamo ya no está vencido o no tiene unidades pendientes.',
            ]);
        }
    }
}
