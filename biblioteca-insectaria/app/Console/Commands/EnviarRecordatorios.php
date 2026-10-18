<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioVencimiento;
use App\Models\Prestamo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatorios extends Command
{
    protected $signature = 'biblioteca:enviar-recordatorios';

    protected $description = 'Envía recordatorios únicos a socios con correo y préstamos próximos a vencer.';

    public function handle(): int
    {
        $fecha = today()->addDays((int) config('biblioteca.dias_recordatorio'));
        $ids = Prestamo::query()
            ->where('estado', 'activo')
            ->whereDate('fecha_vencimiento', $fecha)
            ->whereDoesntHave('recordatorio')
            ->whereHas('socio', fn ($query) => $query->whereNotNull('email')->where('email', '!=', ''))
            ->pluck('id');
        $enviados = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$enviados) {
                $prestamo = Prestamo::query()->with(['socio', 'libro'])->lockForUpdate()->find($id);

                if (! $prestamo || $prestamo->estado !== 'activo' || $prestamo->recordatorio()->exists() || ! $prestamo->socio->email) {
                    return;
                }

                Mail::to($prestamo->socio->email)->send(new RecordatorioVencimiento($prestamo));
                $prestamo->recordatorio()->create([
                    'tipo' => 'vencimiento',
                    'enviado_at' => now(),
                    'canal' => 'email',
                ]);
                $enviados++;
            });
        }

        $this->info("Recordatorios enviados: {$enviados}");

        return self::SUCCESS;
    }
}
