<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\Socio;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $yearExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y', fecha_prestamo)"
            : 'YEAR(fecha_prestamo)';
        $years = Prestamo::query()
            ->selectRaw("{$yearExpression} as anio")
            ->whereNotNull('fecha_prestamo')
            ->groupBy('anio')
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(fn ($year): int => (int) $year);
        $validated = Validator::make($request->query(), [
            'year' => ['nullable', 'integer', Rule::in($years->all())],
        ])->validate();
        $selectedYear = isset($validated['year'])
            ? (int) $validated['year']
            : ($years->first() ?? now()->year);

        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%m', fecha_prestamo)"
            : 'MONTH(fecha_prestamo)';
        $mesesRegistrados = Prestamo::query()
            ->selectRaw("{$monthExpression} as mes, SUM(cantidad) as total")
            ->whereYear('fecha_prestamo', $selectedYear)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy(fn ($row) => (int) $row->mes);
        $meses = collect(range(1, 12))->map(function (int $month) use ($mesesRegistrados, $selectedYear) {
            $fecha = now()->setDate($selectedYear, $month, 1);

            return (object) [
                'mes' => $fecha->format('Y-m'),
                'etiqueta' => $fecha->translatedFormat('M'),
                'total' => (int) ($mesesRegistrados->get($month)?->total ?? 0),
            ];
        });
        $maxPrestamosMes = max(1, (int) $meses->max('total'));

        $librosMasPrestados = $this->queryLibrosMasPrestados()->limit(5)->get();
        $maxLibro = max(1, (int) $librosMasPrestados->max('total'));
        $comunas = $this->agruparCategorias(Socio::query()->whereNotNull('comuna')->where('comuna', '!=', '')->pluck('comuna'));

        return view('admin.dashboard', [
            'prestamosMes' => $meses,
            'maxPrestamosMes' => $maxPrestamosMes,
            'aniosDisponibles' => $years,
            'anioSeleccionado' => $selectedYear,
            'librosMasPrestados' => $librosMasPrestados,
            'maxLibro' => $maxLibro,
            'sociosActivos' => Socio::query()->where('activo', true)->whereDate('vigente_hasta', '>=', today())->count(),
            'prestamosActivos' => (int) Prestamo::query()->where('estado', 'activo')->selectRaw('COALESCE(SUM(cantidad - cantidad_devuelta), 0) as total')->value('total'),
            'comunas' => $comunas,
            'morosos' => (int) Prestamo::query()->where('estado', 'activo')->whereDate('fecha_vencimiento', '<', today())->selectRaw('COALESCE(SUM(cantidad - cantidad_devuelta), 0) as total')->value('total'),
        ]);
    }

    public function librosMasPrestados(): View
    {
        return view('admin.libros-mas-prestados', [
            'libros' => $this->queryLibrosMasPrestados()->paginate(25),
        ]);
    }

    private function queryLibrosMasPrestados(): Builder
    {
        return DB::table('prestamos')
            ->join('libros', 'prestamos.libro_id', '=', 'libros.id')
            ->select('libros.titulo', 'libros.autor')
            ->selectRaw('SUM(prestamos.cantidad) as total')
            ->groupBy('libros.id', 'libros.titulo', 'libros.autor')
            ->orderByDesc('total')
            ->orderBy('libros.titulo');
    }

    private function agruparCategorias($valores): \Illuminate\Support\Collection
    {
        $agregados = [];

        foreach ($valores as $valor) {
            $texto = trim((string) $valor);
            if ($texto === '') {
                continue;
            }

            $clave = $this->normalizarCategoria($texto);
            if ($clave === '') {
                continue;
            }

            if (! isset($agregados[$clave])) {
                $agregados[$clave] = [
                    'nombre' => $this->etiquetaCategoria($texto),
                    'total' => 0,
                ];
            }

            $agregados[$clave]['total']++;
        }

        return collect($agregados)
            ->map(fn (array $categoria) => (object) [
                'nombre' => $categoria['nombre'],
                'total' => (int) $categoria['total'],
            ])
            ->sortByDesc('total')
            ->take(8)
            ->values();
    }

    private function normalizarCategoria(string $valor): string
    {
        $texto = preg_replace('/\s+/', ' ', trim($valor));
        $texto = preg_replace('/[\x00-\x1F\x7F]/u', '', $texto);
        $texto = strtolower($texto);
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
        $texto = preg_replace('/[^a-z0-9 ]/', '', $texto);
        $texto = preg_replace('/\s+/', ' ', trim($texto));

        return $texto;
    }

    private function etiquetaCategoria(string $valor): string
    {
        $texto = trim($valor);
        if ($texto === '') {
            return 'Sin información';
        }

        $clave = $this->normalizarCategoria($texto);

        if ($clave === 'si') {
            return 'Sí';
        }

        if ($clave === 'no') {
            return 'No';
        }

        return trim($texto);
    }
}
