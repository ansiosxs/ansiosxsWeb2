<?php

namespace Tests\Feature;

use App\Models\TurnoAvailability;
use App\Models\TurnoSchedule;
use App\Models\User;
use App\Models\VolunteerReport;
use App\Services\TurnoScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TurnosTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_monthly_draft_with_weekdays_and_configurable_coverage(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.turnos.store'), [
            'year' => 2026,
            'month' => 11,
            'volunteers_per_day' => 3,
        ])->assertRedirect();

        $schedule = TurnoSchedule::query()->firstOrFail();
        $this->assertSame('draft', $schedule->status);
        $this->assertSame(3, $schedule->volunteers_per_day);
        $this->assertSame(30, $schedule->days()->count());
        $this->assertDatabaseHas('turno_days', ['turno_schedule_id' => $schedule->id, 'date' => '2026-11-02 00:00:00']);
        $this->assertDatabaseHas('turno_days', ['turno_schedule_id' => $schedule->id, 'date' => '2026-11-07 00:00:00', 'is_open' => false]);
        $this->assertDatabaseMissing('turno_days', ['turno_schedule_id' => $schedule->id, 'date' => '2026-11-01']);

        $this->actingAs($admin)->get(route('admin.turnos.show', $schedule))
            ->assertOk()
            ->assertSee('Vista de planilla')
            ->assertSee('Lunes')
            ->assertSee('Turnos asignados')
            ->assertSee('Editar fecha');
    }

    public function test_volunteer_can_submit_weekday_preferences_and_specific_date_exceptions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $schedule = $this->createSchedule($admin);

        $this->actingAs($volunteer)->post(route('admin.turnos.disponibilidad', $schedule), [
            'weekdays' => [1, 3],
            'date_overrides' => [
                '2026-11-02' => 'unavailable',
                '2026-11-03' => 'available',
                '2026-11-04' => 'default',
            ],
        ])->assertRedirect(route('admin.turnos.show', $schedule));

        $availability = TurnoAvailability::query()->firstOrFail();
        $this->assertSame([1, 3], $availability->weekdays);
        $this->assertSame([
            '2026-11-02' => 'unavailable',
            '2026-11-03' => 'available',
        ], $availability->date_overrides);

        $this->actingAs($volunteer)->get(route('admin.turnos.show', $schedule))
            ->assertOk()
            ->assertSee('Indica tu disponibilidad');
    }

    public function test_scheduler_builds_a_fair_draft_and_admin_can_publish_it(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteers = collect(range(1, 3))->map(fn () => User::factory()->create(['role' => User::ROLE_VOLUNTARIO]));
        $schedule = $this->createSchedule($admin, 2);

        foreach ($volunteers as $volunteer) {
            TurnoAvailability::create([
                'turno_schedule_id' => $schedule->id,
                'user_id' => $volunteer->id,
                'weekdays' => [1, 2, 3, 4, 5],
                'date_overrides' => [],
            ]);
        }

        $result = app(TurnoScheduler::class)->generate($schedule);
        $this->assertSame(0, $result['understaffed_days']);

        $counts = $schedule->days()->where('is_open', true)->withCount('volunteers')->get()->pluck('volunteers_count');
        $this->assertSame(2, $counts->min());
        $this->assertSame(2, $counts->max());
        $volunteerCounts = DB::table('turno_asignaciones')
            ->whereIn('turno_dia_id', $schedule->days()->select('id'))
            ->selectRaw('COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total');
        $this->assertLessThanOrEqual(1, $volunteerCounts->max() - $volunteerCounts->min());

        $this->actingAs($admin)->post(route('admin.turnos.publicar', $schedule))
            ->assertRedirect(route('admin.turnos.show', $schedule));
        $this->assertDatabaseHas('turno_schedules', ['id' => $schedule->id, 'status' => 'published']);

        $response = $this->actingAs($volunteers->first())->get(route('admin.turnos.calendario', $schedule));
        $response->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->assertSee('TURNOS Y ACTIVIDADES', false)
            ->assertSee('data:image/png;base64,', false)
            ->assertSee('2026', false);
    }

    public function test_calendar_does_not_publish_when_an_open_day_is_understaffed(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $schedule = $this->createSchedule($admin);
        TurnoAvailability::create([
            'turno_schedule_id' => $schedule->id,
            'user_id' => $volunteer->id,
            'weekdays' => [1, 2, 3, 4, 5],
            'date_overrides' => [],
        ]);

        app(TurnoScheduler::class)->generate($schedule);
        $this->actingAs($admin)->post(route('admin.turnos.publicar', $schedule))
            ->assertSessionHasErrors('publicar');
        $this->assertDatabaseHas('turno_schedules', ['id' => $schedule->id, 'status' => 'draft']);
    }

    public function test_date_exceptions_override_weekday_preferences_during_generation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $schedule = $this->createSchedule($admin, 1);
        TurnoAvailability::create([
            'turno_schedule_id' => $schedule->id,
            'user_id' => $volunteer->id,
            'weekdays' => [1],
            'date_overrides' => [
                '2026-11-02' => 'unavailable',
                '2026-11-03' => 'available',
            ],
        ]);

        app(TurnoScheduler::class)->generate($schedule);

        $this->assertFalse($schedule->days()->whereDate('date', '2026-11-02')->firstOrFail()->volunteers->contains($volunteer));
        $this->assertTrue($schedule->days()->whereDate('date', '2026-11-03')->firstOrFail()->volunteers->contains($volunteer));
    }

    public function test_volunteers_can_report_and_admin_can_mark_reports_resolved(): void
    {
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $otherVolunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($volunteer)->post(route('admin.reportes.store'), [
            'title' => 'Luz quemada',
            'category' => 'Mantención',
            'description' => 'La luz de la sala no enciende.',
        ])->assertRedirect(route('admin.reportes.index'));

        $report = VolunteerReport::query()->firstOrFail();
        $this->actingAs($otherVolunteer)->get(route('admin.reportes.index'))
            ->assertOk()
            ->assertDontSee('Luz quemada');

        $this->actingAs($admin)->get(route('admin.reportes.index'))
            ->assertOk()
            ->assertSee('Luz quemada');
        $this->put(route('admin.reportes.estado', $report), ['status' => 'resolved'])
            ->assertRedirect(route('admin.reportes.index'));

        $this->assertDatabaseHas('volunteer_reports', [
            'id' => $report->id,
            'status' => 'resolved',
            'resolved_by' => $admin->id,
        ]);
    }

    public function test_volunteers_cannot_create_or_edit_calendars_and_only_admin_can_manage_activities(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $schedule = $this->createSchedule($admin);
        $this->actingAs($volunteer)->get(route('admin.turnos.create'))->assertForbidden();
        $this->post(route('admin.turnos.actividades.store', $schedule), [
            'date' => '2026-11-07',
            'title' => 'Club de lectura',
            'description' => 'Encuentro mensual.',
        ])->assertForbidden();

        $this->actingAs($admin)->post(route('admin.turnos.actividades.store', $schedule), [
            'date' => '2026-11-07',
            'title' => 'Club de lectura',
            'responsible' => 'Camila',
            'description' => 'Encuentro mensual.',
            'convocatoria' => 'Invitar a socias y socios.',
        ])->assertRedirect(route('admin.turnos.show', $schedule));
        $this->assertDatabaseHas('turno_actividades', [
            'turno_schedule_id' => $schedule->id,
            'title' => 'Club de lectura',
            'responsible' => 'Camila',
            'convocatoria' => 'Invitar a socias y socios.',
        ]);
        $schedule->update(['status' => 'published']);
        $this->actingAs($volunteer)->get(route('admin.turnos.show', $schedule))
            ->assertOk()
            ->assertSee('Actividades del mes')
            ->assertSee('Club de lectura');
        $this->get(route('admin.turnos.calendario', $schedule))
            ->assertOk()
            ->assertSee('Club de lectura');
    }

    public function test_admin_can_open_a_weekend_and_assign_a_large_group_without_a_small_volunteer_cap(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $schedule = $this->createSchedule($admin);
        $saturday = $schedule->days()->whereDate('date', '2026-11-07')->firstOrFail();
        $volunteers = collect(range(1, 12))->map(fn () => User::factory()->create(['role' => User::ROLE_VOLUNTARIO]));

        $this->actingAs($admin)->put(route('admin.turnos.dias.update', [$schedule, $saturday]), [
            'is_open' => '1',
            'volunteers' => $volunteers->pluck('id')->all(),
            'assignment_notes' => 'Actividad de fin de semana',
        ])->assertRedirect(route('admin.turnos.show', $schedule));

        $saturday->refresh();
        $this->assertTrue($saturday->is_open);
        $this->assertSame(12, $saturday->volunteers()->count());
        $this->assertSame('Actividad de fin de semana', $saturday->assignment_notes);
    }

    public function test_historical_csv_import_preserves_the_spreadsheet_notes_and_holiday_closures(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $path = sys_get_temp_dir().'/turnos-'.Str::uuid().'.csv';
        $rows = [
            ['2026'],
            ['MES', 'LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'],
            ['MARZO', 'Lunes 2', 'Martes 3', 'Miércoles 4', 'Jueves 5', 'Viernes 6', '7', '8'],
            ['', 'Seba', 'Fran', 'Angela | Fanzines', 'Hans', 'Pez', '', 'Feriado'],
            ['', '9', '10', '11', '12', '13', '14', '15'],
            ['', 'Nico', 'Seba', 'Vale', 'Hans', 'Fran', '', ''],
            ['ABRIL', '', '', 'MIERCOLES 1', 'JUEVES 2', 'VIERNES 3', 'SABADO 4', 'DOMINGO 5'],
            ['', '', '', 'Club de lectura', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', 'Formulario Actividades', 'https://example.test/form', '', 'Convocatoria por formulario.'],
            ['', '', '', '', '', '', '', '', '', 'Encargadx', 'Detalle', 'Fecha'],
            ['', '', '', '', '', '', '', '', '', 'Ejemplo: Hans', 'Una actividad para el lanzamiento del libro.', '01-04-26'],
        ];
        $handle = fopen($path, 'wb');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        try {
            $this->artisan('biblioteca:importar-turnos', ['archivo' => $path])
                ->expectsOutputToContain('Calendarios importados: 2')
                ->assertExitCode(0);
        } finally {
            @unlink($path);
        }

        $schedule = TurnoSchedule::query()->where('year', 2026)->where('month', 3)->firstOrFail();
        $this->assertSame('published', $schedule->status);
        $this->assertDatabaseHas('turno_days', [
            'turno_schedule_id' => $schedule->id,
            'date' => '2026-03-02 00:00:00',
            'assignment_notes' => 'Seba',
        ]);
        $this->assertDatabaseHas('turno_days', [
            'turno_schedule_id' => $schedule->id,
            'date' => '2026-03-08 00:00:00',
            'is_open' => false,
            'assignment_notes' => 'Feriado',
        ]);
        $this->assertDatabaseMissing('turno_asignaciones', ['turno_dia_id' => $schedule->days()->whereDate('date', '2026-03-02')->value('id')]);
        $this->assertSame(31, $schedule->days()->count());

        $april = TurnoSchedule::query()->where('year', 2026)->where('month', 4)->firstOrFail();
        $this->assertDatabaseHas('turno_actividades', [
            'turno_schedule_id' => $april->id,
            'date' => '2026-04-01 00:00:00',
            'title' => 'Ejemplo de convocatoria (importado)',
            'description' => 'Una actividad para el lanzamiento del libro.',
        ]);
    }

    public function test_historical_csv_merge_preserves_existing_assignments_and_day_states(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $schedule = TurnoSchedule::create([
            'year' => 2026,
            'month' => 3,
            'volunteers_per_day' => 2,
            'created_by' => $admin->id,
            'status' => 'draft',
        ]);
        $existingNote = $schedule->days()->create([
            'date' => '2026-03-02',
            'is_open' => false,
            'assignment_notes' => 'Nota administrativa',
        ]);
        $assignedDay = $schedule->days()->create(['date' => '2026-03-03', 'is_open' => false]);
        $assignedDay->volunteers()->attach($volunteer->id, ['assigned_by' => $admin->id]);

        $path = sys_get_temp_dir().'/turnos-'.Str::uuid().'.csv';
        $rows = [
            ['2026'],
            ['MES', 'LUNES', 'MARTES', 'MIERCOLES'],
            ['MARZO', 'Lunes 2', 'Martes 3', 'Miércoles 4'],
            ['', 'Seba', 'Fran', 'Angela | Fanzines'],
        ];
        $handle = fopen($path, 'wb');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        try {
            $this->artisan('biblioteca:importar-turnos', [
                'archivo' => $path,
                '--merge-existing' => true,
            ])
                ->expectsOutputToContain('Calendarios importados: 0; calendarios fusionados: 1')
                ->assertExitCode(0);
        } finally {
            @unlink($path);
        }

        $this->assertSame('draft', $schedule->fresh()->status);
        $this->assertSame(31, $schedule->days()->count());
        $this->assertFalse($existingNote->fresh()->is_open);
        $this->assertSame('Nota administrativa', $existingNote->fresh()->assignment_notes);
        $this->assertFalse($assignedDay->fresh()->is_open);
        $this->assertSame('Fran', $assignedDay->fresh()->assignment_notes);
        $this->assertSame(1, $assignedDay->volunteers()->count());
        $this->assertDatabaseHas('turno_days', [
            'turno_schedule_id' => $schedule->id,
            'date' => '2026-03-04 00:00:00',
            'is_open' => true,
            'assignment_notes' => 'Angela | Fanzines',
        ]);
        $this->assertDatabaseHas('turno_days', [
            'turno_schedule_id' => $schedule->id,
            'date' => '2026-03-07 00:00:00',
            'is_open' => false,
            'assignment_notes' => null,
        ]);
    }

    private function createSchedule(User $admin, int $volunteersPerDay = 2): TurnoSchedule
    {
        $schedule = TurnoSchedule::create([
            'year' => 2026,
            'month' => 11,
            'volunteers_per_day' => $volunteersPerDay,
            'created_by' => $admin->id,
            'status' => 'draft',
        ]);
        $date = Carbon::create(2026, 11, 1);
        $lastDay = $date->copy()->endOfMonth();

        while ($date->lte($lastDay)) {
            $schedule->days()->create(['date' => $date->toDateString(), 'is_open' => $date->isWeekday()]);
            $date->addDay();
        }

        return $schedule;
    }
}
