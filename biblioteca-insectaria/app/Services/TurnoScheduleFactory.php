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
            $previousAvailabilities = $previousSchedule
                ? $previousSchedule->availabilities()->get()->keyBy('user_id')
                : collect();
            $users = User::query()->where('activo', true)->get();
            $profiles = TurnoVolunteerProfile::query()
                ->whereIn('user_id', $users->pluck('id'))
                ->get()
                ->keyBy('user_id');

            foreach ($users as $user) {
                $profile = $profiles->get($user->id);
                $previousAvailability = $previousAvailabilities->get($user->id);
                TurnoAvailability::create([
                    'turno_schedule_id' => $schedule->id,
                    'user_id' => $user->id,
                    'weekdays' => $previousAvailability?->weekdays ?? $profile?->weekdays ?? [],
                    'date_overrides' => [],
                    'participates' => (bool) ($profile?->enabled ?? false)
                        && (bool) ($previousAvailability?->participates ?? false),
                    'monthly_turns' => $profile?->monthly_turns ?? 2,
                ]);
            }

            return [$schedule, true];
        });
    }
}
