<?php

namespace App\Services;

use App\Models\TurnoSchedule;
use App\Models\User;
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
                ->where('participates', true)
                ->whereHas('volunteer', fn ($query) => $query
                    ->where('role', User::ROLE_VOLUNTARIO)
                    ->where('activo', true))
                ->get();

            $eligibleDaysByVolunteer = [];
            $requestedTurns = [];
            $dayLoads = array_fill_keys($days->modelKeys(), 0);
            $dayDates = $days->mapWithKeys(fn ($day) => [$day->id => $day->date->toDateString()])->all();
            foreach ($days as $day) {
                foreach ($availabilities as $availability) {
                    if ($this->isAvailable($availability, $day->date->toDateString(), $day->date->dayOfWeekIso)) {
                        $eligibleDaysByVolunteer[$availability->user_id][] = $day->id;
                    }
                }
            }

            $volunteerTotal = $availabilities->count();
            $rotation = $volunteerTotal > 0 ? (($schedule->year * 12 + $schedule->month) % $volunteerTotal) : 0;
            $tieBreakRank = [];
            foreach ($availabilities->values() as $rank => $availability) {
                $tieBreakRank[$availability->user_id] = ($rank - $rotation + $volunteerTotal) % $volunteerTotal;
                $requestedTurns[$availability->user_id] = $availability->monthly_turns;
            }

            $orderedVolunteers = $availabilities->sort(function ($first, $second) use ($eligibleDaysByVolunteer, $requestedTurns, $tieBreakRank) {
                return [
                    count($eligibleDaysByVolunteer[$first->user_id] ?? []) / max(1, $requestedTurns[$first->user_id]),
                    $tieBreakRank[$first->user_id],
                ] <=> [
                    count($eligibleDaysByVolunteer[$second->user_id] ?? []) / max(1, $requestedTurns[$second->user_id]),
                    $tieBreakRank[$second->user_id],
                ];
            });

            $assignments = array_fill_keys($days->modelKeys(), []);
            $assignedCounts = array_fill_keys(array_keys($requestedTurns), 0);
            foreach ($orderedVolunteers as $availability) {
                $userId = $availability->user_id;
                $eligibleDayIds = $eligibleDaysByVolunteer[$userId] ?? [];

                for ($turn = 0; $turn < $requestedTurns[$userId]; $turn++) {
                    $availableCandidates = array_values(array_filter(
                        $eligibleDayIds,
                        fn (int $dayId) => ! in_array($userId, $assignments[$dayId], true),
                    ));
                    usort($availableCandidates, fn (int $first, int $second) => [
                        $dayLoads[$first],
                        $dayDates[$first],
                    ] <=> [
                        $dayLoads[$second],
                        $dayDates[$second],
                    ]);
                    $dayId = $availableCandidates[0] ?? null;

                    if ($dayId === null) {
                        break;
                    }

                    $assignments[$dayId][] = $userId;
                    $dayLoads[$dayId]++;
                    $assignedCounts[$userId]++;
                }
            }

            foreach ($days as $day) {
                $day->volunteers()->syncWithPivotValues(
                    $assignments[$day->id],
                    ['assigned_by' => $schedule->created_by],
                );
            }

            return [
                'underassigned_volunteers' => $availabilities->filter(
                    fn ($availability) => ($assignedCounts[$availability->user_id] ?? 0) < $availability->monthly_turns,
                )->count(),
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
