<?php

namespace App\Services;

use App\Models\TurnoAvailability;
use App\Models\TurnoSchedule;
use App\Models\TurnoVolunteerProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TurnoScheduleFactory
{
    public function create(int $year, int $month, User $creator): array
    {
        return DB::transaction(function () use ($year, $month, $creator): array {
            $existing = TurnoSchedule::query()
                ->where('year', $year)
                ->where('month', $month)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return [$existing, false];
            }

            $schedule = TurnoSchedule::create([
                'year' => $year,
                'month' => $month,
                'volunteers_per_day' => 1,
                'created_by' => $creator->id,
                'status' => 'draft',
            ]);

            $date = Carbon::create($year, $month, 1)->startOfMonth();
            $lastDay = $date->copy()->endOfMonth();

            while ($date->lte($lastDay)) {
                $schedule->days()->create([
                    'date' => $date->toDateString(),
                    'is_open' => $date->isWeekday(),
                ]);
                $date->addDay();
            }

            $previousMonth = Carbon::create($year, $month, 1)->subMonthNoOverflow();
            $previousSchedule = TurnoSchedule::query()
                ->where('year', $previousMonth->year)
                ->where('month', $previousMonth->month)
                ->first();
            $previousParticipation = $previousSchedule
                ? $previousSchedule->availabilities()->pluck('participates', 'user_id')
                : collect();
            $profiles = TurnoVolunteerProfile::query()
                ->whereHas('volunteer', fn ($query) => $query
                    ->where('role', User::ROLE_VOLUNTARIO)
                    ->where('activo', true))
                ->with('volunteer')
                ->get();

            foreach ($profiles as $profile) {
                TurnoAvailability::create([
                    'turno_schedule_id' => $schedule->id,
                    'user_id' => $profile->user_id,
                    'weekdays' => $profile->weekdays ?? [],
                    'date_overrides' => [],
                    'participates' => $profile->enabled
                        && (bool) $previousParticipation->get($profile->user_id, false),
                    'monthly_turns' => $profile->monthly_turns,
                ]);
            }

            return [$schedule, true];
        });
    }
}
