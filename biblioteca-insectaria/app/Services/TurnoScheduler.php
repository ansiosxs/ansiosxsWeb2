<?php

namespace App\Services;

use App\Models\TurnoSchedule;
use Illuminate\Support\Facades\DB;

class TurnoScheduler
{
    public function generate(TurnoSchedule $schedule): array
    {
        abort_if($schedule->status !== 'draft', 403, 'Solo se pueden generar propuestas para calendarios en borrador.');

        return DB::transaction(function () use ($schedule): array {
            $days = $schedule->days()
                ->where('is_open', true)
                ->orderBy('date')
                ->get();
            $availabilities = $schedule->availabilities()
                ->with('volunteer')
                ->whereHas('volunteer', fn ($query) => $query
                    ->where('role', 'voluntario')
                    ->where('activo', true))
                ->get();

            $eligibleByDay = [];
            $daysByAvailabilityCount = [];
            $availableDaysCount = [];

            foreach ($availabilities as $availability) {
                $availableDaysCount[$availability->user_id] = 0;
            }

            foreach ($days as $day) {
                $eligible = $availabilities
                    ->filter(fn ($availability) => $this->isAvailable($availability, $day->date->toDateString(), $day->date->dayOfWeekIso))
                    ->values();
                $eligibleByDay[$day->id] = $eligible;
                $daysByAvailabilityCount[$day->id] = $eligible->count();

                foreach ($eligible as $availability) {
                    $availableDaysCount[$availability->user_id]++;
                }
            }

            $volunteerCounts = array_fill_keys(array_keys($availableDaysCount), 0);
            $assignments = [];
            $volunteerIds = array_keys($availableDaysCount);
            sort($volunteerIds);
            $volunteerTotal = count($volunteerIds);
            $rotation = $volunteerTotal > 0 ? (($schedule->year * 12 + $schedule->month) % $volunteerTotal) : 0;
            $tieBreakRank = [];
            foreach ($volunteerIds as $rank => $userId) {
                $tieBreakRank[$userId] = ($rank - $rotation + $volunteerTotal) % $volunteerTotal;
            }
            $orderedDays = $days->sortBy(fn ($day) => [$daysByAvailabilityCount[$day->id], $day->date->toDateString()]);

            foreach ($orderedDays as $day) {
                $candidates = $eligibleByDay[$day->id]
                    ->sort(function ($first, $second) use ($volunteerCounts, $availableDaysCount, $tieBreakRank) {
                        $firstCount = $volunteerCounts[$first->user_id] ?? 0;
                        $secondCount = $volunteerCounts[$second->user_id] ?? 0;

                        return [
                            $firstCount,
                            $availableDaysCount[$first->user_id],
                            $tieBreakRank[$first->user_id],
                        ] <=> [
                            $secondCount,
                            $availableDaysCount[$second->user_id],
                            $tieBreakRank[$second->user_id],
                        ];
                    })
                    ->take($schedule->volunteers_per_day)
                    ->pluck('user_id')
                    ->all();

                $day->volunteers()->syncWithPivotValues($candidates, ['assigned_by' => $schedule->created_by]);
                $assignments[$day->id] = $candidates;

                foreach ($candidates as $userId) {
                    $volunteerCounts[$userId]++;
                }
            }

            return [
                'understaffed_days' => $days->filter(fn ($day) => count($assignments[$day->id] ?? []) < $schedule->volunteers_per_day)->count(),
                'assigned' => array_sum(array_map('count', $assignments)),
            ];
        });
    }

    private function isAvailable($availability, string $date, int $weekday): bool
    {
        $override = $availability->date_overrides[$date] ?? null;

        if ($override === 'available') {
            return true;
        }

        if ($override === 'unavailable') {
            return false;
        }

        return in_array($weekday, $availability->weekdays, true);
    }
}
