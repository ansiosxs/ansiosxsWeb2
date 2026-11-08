<?php

namespace App\Console\Commands;

use App\Services\RecordatorioAutomaticoService;
use Illuminate\Console\Command;

class EnviarRecordatorios extends Command
{
    protected $signature = 'biblioteca:enviar-recordatorios';

    protected $description = 'Prepara y envía recordatorios automáticos de préstamos y membresías vencidos.';

    public function handle(RecordatorioAutomaticoService $service): int
    {
        $enviados = $service->procesarVencidos();
        $this->info("Recordatorios enviados: {$enviados}");

        return self::SUCCESS;
    }
}
