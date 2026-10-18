<?php

namespace App\Services;

use App\Models\Libro;
use App\Models\Prestamo;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrestamoService
{
    public function prestar(int $libroId, int $cantidad, int $socioId, User $voluntario, string $operacionUuid): Prestamo
    {
        try {
            return DB::transaction(function () use ($libroId, $cantidad, $socioId, $voluntario, $operacionUuid) {
                $existente = Prestamo::query()->where('operacion_uuid', $operacionUuid)->lockForUpdate()->first();

                if ($existente) {
                    return $existente;
                }

                $libro = Libro::query()->lockForUpdate()->find($libroId);

                if (! $libro) {
                    throw ValidationException::withMessages(['libro_id' => 'No se encontró el libro seleccionado.']);
                }

                $socio = Socio::query()->lockForUpdate()->find($socioId);

                if (! $socio || ! $socio->activo) {
                    throw ValidationException::withMessages(['socio_id' => 'El socio no está activo.']);
                }

                if ($socio->vigente_hasta->lt(today())) {
                    throw ValidationException::withMessages(['socio_id' => 'La membresía está vencida. Renueva antes de prestar libros.']);
                }

                if ($socio->prestamosActivos()->whereDate('fecha_vencimiento', '<', today())->exists()) {
                    throw ValidationException::withMessages(['socio_id' => 'El socio tiene préstamos vencidos y debe devolverlos antes de retirar otros libros.']);
                }

                $prestadasLibro = (int) Prestamo::query()
                    ->where('libro_id', $libro->id)
                    ->where('estado', 'activo')
                    ->selectRaw('COALESCE(SUM(cantidad - cantidad_devuelta), 0) as total')
                    ->value('total');
                $disponibles = max(0, (int) $libro->cantidad_total - $prestadasLibro);

                if ($cantidad > $disponibles) {
                    throw ValidationException::withMessages([
                        'cantidad' => "Solo hay {$disponibles} unidades disponibles de este libro.",
                    ]);
                }

                $limite = (int) config('biblioteca.max_prestamos_activos');
                $prestadasSocio = $socio->unidadesPrestadas();

                if ($prestadasSocio + $cantidad > $limite) {
                    throw ValidationException::withMessages([
                        'cantidad' => "El socio puede tener hasta {$limite} unidades prestadas y actualmente tiene {$prestadasSocio}.",
                    ]);
                }

                $ahora = now();

                return Prestamo::query()->create([
                    'socio_id' => $socio->id,
                    'libro_id' => $libro->id,
                    'cantidad' => $cantidad,
                    'cantidad_devuelta' => 0,
                    'user_id' => $voluntario->id,
                    'fecha_prestamo' => $ahora,
                    'fecha_vencimiento' => $ahora->copy()->addDays((int) config('biblioteca.plazo_prestamo_dias'))->toDateString(),
                    'estado' => 'activo',
                    'operacion_uuid' => $operacionUuid,
                ]);
            }, 3);
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                $existente = Prestamo::query()->where('operacion_uuid', $operacionUuid)->first();

                if ($existente) {
                    return $existente;
                }
            }

            throw $exception;
        }
    }

    public function devolver(int $prestamoId, int $cantidad, User $voluntario): array
    {
        return DB::transaction(function () use ($prestamoId, $cantidad, $voluntario) {
            $prestamo = Prestamo::query()->lockForUpdate()->find($prestamoId);

            if (! $prestamo || $prestamo->estado !== 'activo') {
                throw ValidationException::withMessages(['prestamo_id' => 'Este préstamo ya no tiene unidades pendientes de devolución.']);
            }

            if ($cantidad > $prestamo->cantidad_pendiente) {
                throw ValidationException::withMessages([
                    'cantidad' => "Solo quedan {$prestamo->cantidad_pendiente} unidades pendientes de este préstamo.",
                ]);
            }

            $cantidadDevuelta = $prestamo->cantidad_devuelta + $cantidad;
            $completo = $cantidadDevuelta === $prestamo->cantidad;
            $prestamo->update([
                'cantidad_devuelta' => $cantidadDevuelta,
                'estado' => $completo ? 'devuelto' : 'activo',
                'fecha_devolucion' => $completo ? now() : null,
                'devuelto_por_user_id' => $voluntario->id,
            ]);

            return [
                'devuelto' => true,
                'completo' => $completo,
                'cantidad_pendiente' => $prestamo->cantidad - $cantidadDevuelta,
                'dias_atraso' => max(0, (int) $prestamo->fecha_vencimiento->diffInDays(today(), false)),
            ];
        }, 3);
    }
}
