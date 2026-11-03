<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TurnoScheduleFactory;
use App\Services\TurnoScheduler;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CrearBorradorTurnosSiguienteMes extends Command
{
    protected $signature = 'turnos:crear-borrador-siguiente';

    protected $description = 'Crea el borrador de turnos para el mes siguiente';

    public function handle(TurnoScheduleFactory $scheduleFactory, TurnoScheduler $scheduler): int
    {
        $administrator = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('activo', true)
            ->orderBy('id')
            ->first();

        if (! $administrator) {
            $this->error('No se encontró una cuenta administradora activa para registrar el calendario.');

            return self::FAILURE;
        }

        $nextMonth = Carbon::now()->addMonthNoOverflow();
        [$schedule, $created] = $scheduleFactory->create($nextMonth->year, $nextMonth->month, $administrator);

        if ($created) {
            $result = $scheduler->generate($schedule);
            $this->info("Se creó el borrador de turnos {$schedule->month_label} con {$result['assigned']} asignaciones.");

            if ($result['underassigned_volunteers'] > 0) {
                $this->warn("{$result['underassigned_volunteers']} voluntarios no alcanzan su cantidad mensual según sus preferencias.");
            }
        } else {
            $this->info("El calendario {$schedule->month_label} ya existía; no se modificó.");
        }

        return self::SUCCESS;
    }
}
