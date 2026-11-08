<?php

namespace App\Services;

use App\Mail\RecordatorioAutomatico as CorreoRecordatorioAutomatico;
use App\Mail\RecordatorioVencimiento;
use App\Models\ConfiguracionGeneral;
use App\Models\ConfiguracionRecordatorio;
use App\Models\Prestamo;
use App\Models\RecordatorioAutomatico;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class RecordatorioAutomaticoService
{
    public function procesarVencidos(): int
    {
        $configuracion = ConfiguracionRecordatorio::query()->findOrFail(1);
        if (! $configuracion->automaticos_activos) {
            return 0;
        }

        $enviados = 0;
        $diasRecordatorio = ConfiguracionGeneral::actual()->dias_recordatorio;
        if ($diasRecordatorio > 0) {
            $fechaProximoVencimiento = today()->addDays($diasRecordatorio);
            Prestamo::query()
                ->with('socio')
                ->where('estado', 'activo')
                ->whereDate('fecha_vencimiento', $fechaProximoVencimiento)
                ->whereDoesntHave('recordatorio')
                ->whereHas('socio', fn ($query) => $query->whereNotNull('email')->where('email', '!=', ''))
                ->chunkById(100, function ($prestamos) use ($configuracion, $fechaProximoVencimiento, &$enviados) {
                    foreach ($prestamos as $prestamo) {
                        $recordatorio = $this->crearSiNoExiste(
                            'prestamo_proximo',
                            $prestamo->id,
                            $prestamo->socio_id,
                            $prestamo->id,
                            $fechaProximoVencimiento->toDateString(),
                            $prestamo->socio->email,
                        );

                        if (! $configuracion->preguntar_antes && $recordatorio && $this->enviar($recordatorio->id)) {
                            $enviados++;
                        }
                    }
                });
        }

        Prestamo::query()
            ->with('socio')
            ->where('estado', 'activo')
            ->whereDate('fecha_vencimiento', '<', today())
            ->whereHas('socio', fn ($query) => $query->whereNotNull('email')->where('email', '!=', ''))
            ->chunkById(100, function ($prestamos) use ($configuracion, &$enviados) {
                foreach ($prestamos as $prestamo) {
                    $recordatorio = $this->crearSiNoExiste(
                        'prestamo_vencido',
                        $prestamo->id,
                        $prestamo->socio_id,
                        $prestamo->id,
                        $prestamo->fecha_vencimiento->toDateString(),
                        $prestamo->socio->email,
                    );

                    if (! $configuracion->preguntar_antes && $recordatorio && $this->enviar($recordatorio->id)) {
                        $enviados++;
                    }
                }
            });

        Socio::query()
            ->where('activo', true)
            ->whereDate('vigente_hasta', '<', today())
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->chunkById(100, function ($socios) use ($configuracion, &$enviados) {
                foreach ($socios as $socio) {
                    $recordatorio = $this->crearSiNoExiste(
                        'membresia_vencida',
                        $socio->id,
                        $socio->id,
                        null,
                        $socio->vigente_hasta->toDateString(),
                        $socio->email,
                    );

                    if (! $configuracion->preguntar_antes && $recordatorio && $this->enviar($recordatorio->id)) {
                        $enviados++;
                    }
                }
            });

        return $enviados;
    }

    public function enviar(int $recordatorioId, ?User $autorizadoPor = null): bool
    {
        return DB::transaction(function () use ($recordatorioId, $autorizadoPor): bool {
            $recordatorio = RecordatorioAutomatico::query()
                ->with(['socio', 'prestamo.libro'])
                ->lockForUpdate()
                ->findOrFail($recordatorioId);

            if ($recordatorio->estado !== 'pendiente') {
                return false;
            }

            if (! $this->sigueVigenteParaEnvio($recordatorio)) {
                $recordatorio->update(['estado' => 'cancelado']);

                return false;
            }

            if ($recordatorio->tipo === 'prestamo_proximo') {
                if ($recordatorio->prestamo->recordatorio()->exists()) {
                    $recordatorio->update(['estado' => 'cancelado']);

                    return false;
                }

                Mail::to($recordatorio->email)->send(new RecordatorioVencimiento($recordatorio->prestamo));
                $recordatorio->prestamo->recordatorio()->create([
                    'tipo' => 'vencimiento',
                    'enviado_at' => now(),
                    'canal' => 'email',
                ]);
                $recordatorio->update([
                    'estado' => 'enviado',
                    'autorizado_por_user_id' => $autorizadoPor?->id,
                    'enviado_at' => now(),
                ]);

                return true;
            }

            $configuracion = ConfiguracionRecordatorio::query()->findOrFail(1);
            $esPrestamo = $recordatorio->tipo === 'prestamo_vencido';
            $plantilla = $esPrestamo ? 'prestamo' : 'membresia';
            $reemplazos = [
                '{{nombre}}' => $recordatorio->socio->nombres,
                '{{fecha_vencimiento}}' => $recordatorio->fecha_evento->format('d/m/Y'),
                '{{libro}}' => $recordatorio->prestamo->libro->titulo ?? '',
            ];
            $urlPostergacion = $esPrestamo
                ? URL::temporarySignedRoute(
                    'prestamos.postergar.confirmar',
                    now()->addDays(30),
                    ['prestamo' => $recordatorio->prestamo_id, 'fecha_vencimiento' => $recordatorio->fecha_evento->toDateString()],
                )
                : null;

            Mail::to($recordatorio->email)->send(new CorreoRecordatorioAutomatico(
                strtr($configuracion->{"{$plantilla}_asunto"}, $reemplazos),
                strtr($configuracion->{"{$plantilla}_cuerpo"}, $reemplazos),
                $urlPostergacion,
            ));

            $recordatorio->update([
                'estado' => 'enviado',
                'autorizado_por_user_id' => $autorizadoPor?->id,
                'enviado_at' => now(),
            ]);

            return true;
        });
    }

    public function descartar(int $recordatorioId): bool
    {
        $recordatorio = RecordatorioAutomatico::query()->findOrFail($recordatorioId);

        if ($recordatorio->estado !== 'pendiente') {
            return false;
        }

        $recordatorio->update(['estado' => 'cancelado']);

        return true;
    }

    private function crearSiNoExiste(
        string $tipo,
        int $referenciaId,
        int $socioId,
        ?int $prestamoId,
        string $fechaEvento,
        string $email,
    ): ?RecordatorioAutomatico {
        $now = now();
        DB::table('recordatorios_automaticos')->insertOrIgnore([
            'tipo' => $tipo,
            'referencia_id' => $referenciaId,
            'socio_id' => $socioId,
            'prestamo_id' => $prestamoId,
            'fecha_evento' => $fechaEvento,
            'email' => $email,
            'estado' => 'pendiente',
            'solicitado_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return RecordatorioAutomatico::query()
            ->where('tipo', $tipo)
            ->where('referencia_id', $referenciaId)
            ->whereDate('fecha_evento', $fechaEvento)
            ->where('estado', 'pendiente')
            ->first();
    }

    private function sigueVigenteParaEnvio(RecordatorioAutomatico $recordatorio): bool
    {
        if (in_array($recordatorio->tipo, ['prestamo_proximo', 'prestamo_vencido'], true)) {
            $prestamo = $recordatorio->prestamo;

            return $prestamo
                && $prestamo->estado === 'activo'
                && $prestamo->fecha_vencimiento->isSameDay($recordatorio->fecha_evento)
                && ($recordatorio->tipo === 'prestamo_proximo'
                    ? $prestamo->fecha_vencimiento->gte(today())
                    : $prestamo->fecha_vencimiento->lt(today()));
        }

        $socio = $recordatorio->socio;

        return $socio
            && $socio->activo
            && $socio->vigente_hasta->lt(today())
            && $socio->vigente_hasta->isSameDay($recordatorio->fecha_evento);
    }
}
