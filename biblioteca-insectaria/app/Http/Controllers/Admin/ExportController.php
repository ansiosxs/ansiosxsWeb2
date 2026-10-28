<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Libro;
use App\Models\Prestamo;
use App\Models\Socio;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function libros(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $filename = 'inventario-libros.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['id', 'titulo', 'autor', 'isbn', 'editorial', 'anio', 'cantidad_total', 'cantidad_disponible', 'mueble', 'created_at', 'updated_at']);

            Libro::query()
                ->with('mueble:id,nombre')
                ->withUnidadesPrestadas()
                ->orderBy('titulo')
                ->chunkById(250, function ($libros) use ($handle) {
                    foreach ($libros as $libro) {
                        fputcsv($handle, [
                            $libro->id,
                            $libro->titulo,
                            $libro->autor,
                            $libro->isbn,
                            $libro->editorial,
                            $libro->anio,
                            $libro->cantidad_total,
                            $libro->cantidad_disponible,
                            $libro->mueble?->nombre ?? '',
                            $libro->created_at?->toDateTimeString(),
                            $libro->updated_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function socios(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $filename = 'socios.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['id', 'run', 'nombres', 'apellidos', 'telefono', 'email', 'comuna', 'ocupacion', 'fecha_inscripcion', 'vigente_hasta', 'activo', 'created_at', 'updated_at']);

            Socio::query()
                ->orderBy('apellidos')
                ->orderBy('nombres')
                ->chunkById(250, function ($socios) use ($handle) {
                    foreach ($socios as $socio) {
                        fputcsv($handle, [
                            $socio->id,
                            $socio->run,
                            $socio->nombres,
                            $socio->apellidos,
                            $socio->telefono,
                            $socio->email,
                            $socio->comuna,
                            $socio->ocupacion,
                            $socio->fecha_inscripcion?->toDateString(),
                            $socio->vigente_hasta?->toDateString(),
                            $socio->activo ? '1' : '0',
                            $socio->created_at?->toDateTimeString(),
                            $socio->updated_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function prestamos(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $filename = 'prestamos.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['id', 'socio_id', 'socio_run', 'socio_nombre', 'libro_id', 'libro_titulo', 'cantidad', 'cantidad_devuelta', 'cantidad_pendiente', 'estado', 'fecha_prestamo', 'fecha_vencimiento', 'fecha_devolucion', 'user_id', 'created_at', 'updated_at']);

            Prestamo::query()
                ->with(['socio', 'libro'])
                ->orderBy('fecha_prestamo', 'desc')
                ->chunkById(250, function ($prestamos) use ($handle) {
                    foreach ($prestamos as $prestamo) {
                        fputcsv($handle, [
                            $prestamo->id,
                            $prestamo->socio_id,
                            $prestamo->socio?->run,
                            trim(($prestamo->socio?->nombres ?? '').' '.($prestamo->socio?->apellidos ?? '')),
                            $prestamo->libro_id,
                            $prestamo->libro?->titulo,
                            $prestamo->cantidad,
                            $prestamo->cantidad_devuelta,
                            $prestamo->cantidad_pendiente,
                            $prestamo->estado,
                            $prestamo->fecha_prestamo?->toDateTimeString(),
                            $prestamo->fecha_vencimiento?->toDateString(),
                            $prestamo->fecha_devolucion?->toDateTimeString(),
                            $prestamo->user_id,
                            $prestamo->created_at?->toDateTimeString(),
                            $prestamo->updated_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function todo(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return response()->streamDownload(function () {
            $handle = fopen('php://temp', 'r+');
            $header = ['tipo', 'id', 'run', 'nombres', 'apellidos', 'titulo', 'autor', 'isbn', 'editorial', 'anio', 'cantidad_total', 'cantidad_disponible', 'telefono', 'email', 'comuna', 'ocupacion', 'fecha_inscripcion', 'vigente_hasta', 'activo', 'cantidad', 'cantidad_devuelta', 'cantidad_pendiente', 'estado', 'fecha_prestamo', 'fecha_vencimiento', 'fecha_devolucion', 'user_id'];
            fputcsv($handle, $header);

            Libro::query()->with('mueble:id,nombre')->withUnidadesPrestadas()->orderBy('titulo')->chunkById(250, function ($libros) use ($handle) {
                foreach ($libros as $libro) {
                    fputcsv($handle, [
                        'libro',
                        $libro->id,
                        '', '', '',
                        $libro->titulo,
                        $libro->autor,
                        $libro->isbn,
                        $libro->editorial,
                        $libro->anio,
                        $libro->cantidad_total,
                        $libro->cantidad_disponible,
                        '', '', '', '',
                        '', '', '',
                        '', '', '', '',
                        '', '', '',
                    ]);
                }
            });

            Socio::query()->orderBy('apellidos')->orderBy('nombres')->chunkById(250, function ($socios) use ($handle) {
                foreach ($socios as $socio) {
                    fputcsv($handle, [
                        'socio',
                        $socio->id,
                        $socio->run,
                        $socio->nombres,
                        $socio->apellidos,
                        '', '', '', '', '', '', '',
                        $socio->telefono,
                        $socio->email,
                        $socio->comuna,
                        $socio->ocupacion,
                        $socio->fecha_inscripcion?->toDateString(),
                        $socio->vigente_hasta?->toDateString(),
                        $socio->activo ? '1' : '0',
                        '', '', '', '', '', '', '',
                    ]);
                }
            });

            Prestamo::query()->with(['socio', 'libro'])->orderBy('fecha_prestamo', 'desc')->chunkById(250, function ($prestamos) use ($handle) {
                foreach ($prestamos as $prestamo) {
                    fputcsv($handle, [
                        'prestamo',
                        $prestamo->id,
                        $prestamo->socio?->run,
                        $prestamo->socio?->nombres,
                        $prestamo->socio?->apellidos,
                        $prestamo->libro?->titulo,
                        $prestamo->libro?->autor,
                        $prestamo->libro?->isbn,
                        $prestamo->libro?->editorial,
                        $prestamo->libro?->anio,
                        '', '',
                        '', '', '', '',
                        '', '', '',
                        $prestamo->cantidad,
                        $prestamo->cantidad_devuelta,
                        $prestamo->cantidad_pendiente,
                        $prestamo->estado,
                        $prestamo->fecha_prestamo?->toDateTimeString(),
                        $prestamo->fecha_vencimiento?->toDateString(),
                        $prestamo->fecha_devolucion?->toDateTimeString(),
                        $prestamo->user_id,
                    ]);
                }
            });

            rewind($handle);
            echo stream_get_contents($handle);
            fclose($handle);
        }, 'biblioteca-export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
