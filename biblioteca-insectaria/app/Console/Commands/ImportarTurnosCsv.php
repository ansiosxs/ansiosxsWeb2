<?php

namespace App\Console\Commands;

use App\Models\TurnoActivity;
use App\Models\TurnoSchedule;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportarTurnosCsv extends Command
{
    protected $signature = 'biblioteca:importar-turnos
        {archivo? : Ruta al CSV de turnos}
        {--year=2026 : Año de los calendarios del archivo}
        {--merge-existing : Completa días faltantes y añade notas sin sobrescribir datos existentes}';

    protected $description = 'Importa calendarios históricos de turnos desde un CSV de planilla';

    private const MONTHS = [
        'enero' => 1,
        'febrero' => 2,
        'marzo' => 3,
        'abril' => 4,
        'mayo' => 5,
        'junio' => 6,
        'julio' => 7,
        'agosto' => 8,
        'septiembre' => 9,
        'octubre' => 10,
        'noviembre' => 11,
        'diciembre' => 12,
    ];

    public function handle(): int
    {
        $path = $this->argument('archivo')
            ? $this->argument('archivo')
            : dirname(base_path()).'/Inscripción de Socixs y Préstamos - Turnos Insectaria 2026.csv';
        $year = (int) $this->option('year');
        $administrator = User::query()->where('role', User::ROLE_ADMIN)->where('activo', true)->orderBy('id')->first();

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("No se puede leer el archivo CSV: {$path}");

            return self::FAILURE;
        }

        if (! $administrator) {
            $this->error('No se encontró una cuenta administradora activa para registrar la importación.');

            return self::FAILURE;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $this->error("No se pudo abrir el archivo CSV: {$path}");

            return self::FAILURE;
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(fn ($value) => trim((string) $value), $row);
        }
        fclose($handle);

        $months = $this->parseMonths($rows, $year);
        if ($months === []) {
            $this->error('No se encontraron meses con fechas en el CSV.');

            return self::FAILURE;
        }

        $imported = 0;
        $skipped = 0;
        $daysImported = 0;
        $notesImported = 0;
        $merged = 0;
        $mergeExisting = (bool) $this->option('merge-existing');

        DB::transaction(function () use ($months, $rows, $year, $administrator, $mergeExisting, &$imported, &$skipped, &$daysImported, &$notesImported, &$merged) {
            foreach ($months as $month => $entries) {
                $schedule = TurnoSchedule::query()->where('year', $year)->where('month', $month)->first();
                if ($schedule && ! $mergeExisting) {
                    $skipped++;
                    continue;
                }

                if ($schedule) {
                    [$addedDays, $addedNotes] = $this->mergeExistingSchedule($schedule, $entries, $year, $month);
                    $daysImported += $addedDays;
                    $notesImported += $addedNotes;
                    $merged++;
                    continue;
                }

                $isPastMonth = Carbon::create($year, $month, 1)->lt(now()->startOfMonth());
                $schedule = TurnoSchedule::create([
                    'year' => $year,
                    'month' => $month,
                    'volunteers_per_day' => 2,
                    'status' => $isPastMonth ? 'published' : 'draft',
                    'created_by' => $administrator->id,
                    'published_at' => $isPastMonth ? now() : null,
                ]);

                $days = [];
                $date = Carbon::create($year, $month, 1);
                $lastDay = $date->copy()->endOfMonth();

                while ($date->lte($lastDay)) {
                    $entry = $entries[$date->toDateString()] ?? [];
                    $note = trim($entry['note'] ?? '');
                    $days[] = array_merge(['date' => $date->toDateString()], $this->dayAttributes($date, $note));
                    $date->addDay();
                }

                $schedule->days()->createMany($days);
                $daysImported += count($days);
                $notesImported += collect($days)->whereNotNull('assignment_notes')->count();
                $this->importActivityReference($rows, $month, $year, $administrator, $schedule);
                $imported++;
            }
        });

        $this->info("Calendarios importados: {$imported}; calendarios fusionados: {$merged}; días añadidos: {$daysImported}; notas añadidas: {$notesImported}; meses omitidos por existir: {$skipped}.");
        $this->comment('Los nombres de la planilla se conservaron como notas; no se asociaron a cuentas automáticamente.');

        return self::SUCCESS;
    }

    private function mergeExistingSchedule(TurnoSchedule $schedule, array $entries, int $year, int $month): array
    {
        $existingDays = $schedule->days->keyBy(fn ($day) => Carbon::parse($day->date)->toDateString());
        $daysAdded = 0;
        $notesAdded = 0;
        $date = Carbon::create($year, $month, 1);
        $lastDay = $date->copy()->endOfMonth();

        while ($date->lte($lastDay)) {
            $dateKey = $date->toDateString();
            $note = trim($entries[$dateKey]['note'] ?? '');
            $day = $existingDays->get($dateKey);

            if (! $day) {
                $schedule->days()->create(array_merge(
                    ['date' => $dateKey],
                    $this->dayAttributes($date, $note),
                ));
                $daysAdded++;
                $notesAdded += $note !== '' ? 1 : 0;
            } elseif ($note !== '' && trim((string) $day->assignment_notes) === '') {
                $day->update(['assignment_notes' => $note]);
                $notesAdded++;
            }

            $date->addDay();
        }

        return [$daysAdded, $notesAdded];
    }

    private function dayAttributes(Carbon $date, string $note): array
    {
        $closedByNote = preg_match('/cerrad|feriad/iu', $note) === 1;
        $openByDefault = $date->isWeekday();
        if ($date->isWeekend() && $note !== '' && ! $closedByNote) {
            $openByDefault = true;
        }

        return [
            'is_open' => ! $closedByNote && $openByDefault,
            'assignment_notes' => $note !== '' ? $note : null,
        ];
    }

    private function parseMonths(array $rows, int $year): array
    {
        $months = [];
        $currentMonth = null;
        $rowCount = count($rows);

        for ($index = 0; $index < $rowCount; $index++) {
            $firstCell = mb_strtolower(trim($rows[$index][0] ?? ''));
            if (isset(self::MONTHS[$firstCell])) {
                $currentMonth = self::MONTHS[$firstCell];
                $months[$currentMonth] ??= [];
            }

            if (! $currentMonth) {
                continue;
            }

            $dateColumns = [];
            for ($column = 1; $column <= 7; $column++) {
                $raw = $rows[$index][$column] ?? '';
                if (preg_match('/^\s*(?:(?:lunes|martes|mi[eé]rcoles|jueves|viernes|s[aá]bado|domingo)\s*)?(\d{1,2})\s*$/iu', $raw, $matches)) {
                    $dayNumber = (int) $matches[1];
                    if ($dayNumber >= 1 && $dayNumber <= 31) {
                        $dateColumns[$column] = $dayNumber;
                    }
                }
            }

            if ($dateColumns === []) {
                continue;
            }

            $assignmentRow = $rows[$index + 1] ?? [];
            foreach ($dateColumns as $column => $dayNumber) {
                $date = $this->dateForWeekday($year, $currentMonth, $dayNumber, $column);
                if (! $date) {
                    continue;
                }

                $note = trim($assignmentRow[$column] ?? '');
                if ($note !== '') {
                    $months[$currentMonth][$date] = ['note' => $note];
                }
            }
        }

        return $months;
    }

    private function dateForWeekday(int $year, int $month, int $dayNumber, int $weekday): ?string
    {
        $monthStart = Carbon::create($year, $month, 1);
        $lastDay = $monthStart->copy()->endOfMonth()->day;
        if ($dayNumber > $lastDay) {
            return null;
        }

        $date = $monthStart->copy()->day($dayNumber);
        if ($date->dayOfWeekIso !== $weekday) {
            $candidates = [];
            for ($day = 1; $day <= $lastDay; $day++) {
                $candidate = Carbon::create($year, $month, $day);
                if ($candidate->dayOfWeekIso === $weekday) {
                    $candidates[] = $candidate;
                }
            }
            usort($candidates, fn (Carbon $first, Carbon $second) => abs($first->day - $dayNumber) <=> abs($second->day - $dayNumber));
            $date = $candidates[0] ?? null;
        }

        return $date?->toDateString();
    }

    private function importActivityReference(array $rows, int $month, int $year, User $administrator, TurnoSchedule $schedule): void
    {
        foreach ($rows as $index => $row) {
            if (mb_strtolower(trim($row[10] ?? '')) !== 'detalle') {
                continue;
            }

            $data = $rows[$index + 1] ?? [];
            $description = trim($data[10] ?? '');
            $rawDate = trim($data[11] ?? '');
            if ($description === '' || ! preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{2,4})$/', $rawDate, $dateParts)) {
                continue;
            }

            $activityYear = (int) $dateParts[3];
            if (strlen($dateParts[3]) === 2) {
                $activityYear += 2000;
            }
            $activityDay = (int) $dateParts[1];
            $activityMonth = (int) $dateParts[2];
            if (! checkdate($activityMonth, $activityDay, $activityYear)
                || $activityYear !== $year
                || $activityMonth !== $month) {
                continue;
            }
            $activityDate = Carbon::create($activityYear, $activityMonth, $activityDay);

            $formUrl = trim($rows[$index - 1][10] ?? '');
            $context = trim($rows[$index - 1][12] ?? '');
            $notice = 'Referencia importada desde la planilla; revisar antes de tratarla como una actividad confirmada.';
            if ($context !== '') {
                $notice .= "\n".$context;
            }
            if ($formUrl !== '') {
                $notice .= "\nFormulario de actividades: ".$formUrl;
            }

            TurnoActivity::query()->firstOrCreate(
                [
                    'turno_schedule_id' => $schedule->id,
                    'date' => $activityDate->toDateString(),
                    'title' => 'Ejemplo de convocatoria (importado)',
                ],
                [
                    'description' => $description,
                    'convocatoria' => $notice,
                    'created_by' => $administrator->id,
                ],
            );
        }
    }
}
