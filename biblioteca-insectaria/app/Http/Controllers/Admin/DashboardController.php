<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\Socio;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', fecha_prestamo)"
            : "DATE_FORMAT(fecha_prestamo, '%Y-%m')";
        $mesesRegistrados = Prestamo::query()
            ->selectRaw("{$monthExpression} as mes, SUM(cantidad) as total")
            ->where('fecha_prestamo', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');
        $meses = collect(range(11, 0))->map(function (int $mesesAtras) use ($mesesRegistrados) {
            $fecha = now()->startOfMonth()->subMonths($mesesAtras);
            $mes = $fecha->format('Y-m');

            return (object) [
                'mes' => $mes,
                'etiqueta' => $fecha->translatedFormat('M'),
                'total' => (int) ($mesesRegistrados->get($mes)?->total ?? 0),
            ];
        });
        $maxPrestamosMes = max(1, (int) $meses->max('total'));

        $librosMasPrestados = DB::table('prestamos')
            ->join('libros', 'prestamos.libro_id', '=', 'libros.id')
            ->select('libros.titulo', DB::raw('SUM(prestamos.cantidad) as total'))
            ->groupBy('libros.id', 'libros.titulo')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $maxLibro = max(1, (int) $librosMasPrestados->max('total'));

        return view('admin.dashboard', [
            'prestamosMes' => $meses,
            'maxPrestamosMes' => $maxPrestamosMes,
            'librosMasPrestados' => $librosMasPrestados,
            'maxLibro' => $maxLibro,
            'sociosActivos' => Socio::query()->where('activo', true)->whereDate('vigente_hasta', '>=', today())->count(),
            'prestamosActivos' => (int) Prestamo::query()->where('estado', 'activo')->selectRaw('COALESCE(SUM(cantidad - cantidad_devuelta), 0) as total')->value('total'),
            'comunas' => Socio::query()->select('comuna', DB::raw('COUNT(*) as total'))->groupBy('comuna')->orderByDesc('total')->limit(8)->get(),
            'ocupaciones' => Socio::query()->select('ocupacion', DB::raw('COUNT(*) as total'))->whereNotNull('ocupacion')->groupBy('ocupacion')->orderByDesc('total')->limit(8)->get(),
            'morosos' => (int) Prestamo::query()->where('estado', 'activo')->whereDate('fecha_vencimiento', '<', today())->selectRaw('COALESCE(SUM(cantidad - cantidad_devuelta), 0) as total')->value('total'),
        ]);
    }
}
