<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turno_volunteer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->json('weekdays');
            $table->unsignedTinyInteger('monthly_turns')->default(2);
            $table->timestamps();
        });

        Schema::table('turno_availabilities', function (Blueprint $table) {
            $table->boolean('participates')->default(false);
            $table->unsignedTinyInteger('monthly_turns')->default(2);
        });

        $latestByVolunteer = DB::table('turno_availabilities')
            ->join('turno_schedules', 'turno_schedules.id', '=', 'turno_availabilities.turno_schedule_id')
            ->orderByDesc('turno_schedules.year')
            ->orderByDesc('turno_schedules.month')
            ->orderByDesc('turno_availabilities.id')
            ->get(['turno_availabilities.user_id', 'turno_availabilities.weekdays']);
        $seen = [];
        $profiles = [];

        foreach ($latestByVolunteer as $availability) {
            if (isset($seen[$availability->user_id])) {
                continue;
            }

            $seen[$availability->user_id] = true;
            $weekdays = json_decode($availability->weekdays, true);
            $profiles[] = [
                'user_id' => $availability->user_id,
                'enabled' => true,
                'weekdays' => json_encode(array_values(array_unique(array_map(
                    'intval',
                    array_filter(
                        is_array($weekdays) ? $weekdays : [],
                        fn ($weekday) => is_numeric($weekday) && (int) $weekday >= 1 && (int) $weekday <= 5,
                    ),
                )))),
                'monthly_turns' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($profiles !== []) {
            DB::table('turno_volunteer_profiles')->insert($profiles);
            DB::table('turno_availabilities')->update(['participates' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('turno_availabilities', function (Blueprint $table) {
            $table->dropColumn(['participates', 'monthly_turns']);
        });

        Schema::dropIfExists('turno_volunteer_profiles');
    }
};
