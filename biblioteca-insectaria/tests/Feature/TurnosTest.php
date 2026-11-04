<?php

namespace Tests\Feature;

use App\Models\TurnoAvailability;
use App\Models\TurnoSchedule;
use App\Models\TurnoVolunteerProfile;
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

    public function test_admin_creates_a_monthly_draft_with_weekdays(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.turnos.store'), [
            'year' => 2026,
            'month' => 11,
        ])->assertRedirect();

        $schedule = TurnoSchedule::query()->firstOrFail();
        $this->assertSame('draft', $schedule->status);
        $this->assertSame(1, $schedule->volunteers_per_day);
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

    public function test_admin_can_save_roster_preferences_and_month_participation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $schedule = $this->createSchedule($admin);

        $this->actingAs($admin)
            ->get(route('admin.turnos.show', $schedule))
            ->assertOk()
            ->assertSee('Personas que pueden abrir')
            ->assertSee(route('admin.usuarios.create'), false)
            ->assertSee(route('admin.usuarios.edit', $volunteer), false)
            ->assertSee(route('admin.usuarios.destroy', $volunteer), false)
            ->assertSee('Agregar voluntario')
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->assertSee('Participa este mes')
            ->assertSee($volunteer->name)
            ->assertSee($editor->name)
            ->assertSee($admin->name)
            ->assertDontSee('form id="eliminar-voluntario-' . $admin->id . '"', false);

        $this->put(route('admin.turnos.voluntarios.update', $schedule), [
            'volunteers' => [
                $volunteer->id => [
                    'enabled' => 1,
                    'participates' => 1,
                    'weekdays' => [1, 3],
                    'monthly_turns' => 4,
                ],
                $editor->id => [
                    'enabled' => 1,
                    'participates' => 1,
                    'weekdays' => [1],
                    'monthly_turns' => 1,
                ],
            ],
        ])->assertRedirect(route('admin.turnos.show', $schedule));

        $profile = TurnoVolunteerProfile::query()->where('user_id', $volunteer->id)->firstOrFail();
        $this->assertTrue($profile->enabled);
        $this->assertSame([1, 3], $profile->weekdays);
        $this->assertSame(4, $profile->monthly_turns);

        $availability = TurnoAvailability::query()
            ->where('turno_schedule_id', $schedule->id)
            ->where('user_id', $volunteer->id)
            ->firstOrFail();
        $this->assertSame([1, 3], $availability->weekdays);
        $this->assertTrue($availability->participates);
        $this->assertSame(4, $availability->monthly_turns);

        $editorAvailability = TurnoAvailability::query()
            ->where('turno_schedule_id', $schedule->id)
            ->where('user_id', $editor->id)
            ->firstOrFail();
        $this->assertTrue($editorAvailability->participates);
        $result = app(TurnoScheduler::class)->generate($schedule);
        $this->assertSame(5, $result['assigned']);
        $editorAssignedDay = $schedule->days()
            ->whereHas('volunteers', fn ($query) => $query->where('users.id', $editor->id))
            ->firstOrFail();
        $this->assertSame(1, $editorAssignedDay->date->dayOfWeekIso);

        $manualDay = $schedule->days()->whereDate('date', '2026-11-03')->firstOrFail();
        $this->actingAs($admin)
            ->put(route('admin.turnos.dias.update', [$schedule, $manualDay]), [
                'is_open' => 1,
                'volunteers' => [$editor->id],
            ])
            ->assertRedirect(route('admin.turnos.show', $schedule));
        $this->assertDatabaseHas('turno_asignaciones', [
            'turno_dia_id' => $manualDay->id,
            'user_id' => $editor->id,
        ]);

        $this->actingAs($volunteer)->get(route('admin.turnos.show', $schedule))
            ->assertOk()
            ->assertDontSee('Personas que pueden abrir');
    }

    public function test_admin_cannot_delete_volunteer_with_turno_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $schedule = $this->createSchedule($admin);

        TurnoAvailability::create([
            'turno_schedule_id' => $schedule->id,
            'user_id' => $volunteer->id,
            'weekdays' => [1],
            'date_overrides' => [],
            'participates' => true,
            'monthly_turns' => 1,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.turnos.show', $schedule))
            ->delete(route('admin.usuarios.destroy', $volunteer))
            ->assertRedirect(route('admin.turnos.show', $schedule))
            ->assertSessionHasErrors('usuario');

        $this->assertDatabaseHas('users', ['id' => $volunteer->id]);
    }

    public function test_monthly_command_creates_next_draft_and_copies_participation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        TurnoVolunteerProfile::create([
            'user_id' => $volunteer->id,
            'enabled' => true,
            'weekdays' => [1, 4],
            'monthly_turns' => 3,
        ]);
        $previousSchedule = TurnoSchedule::create([
            'year' => 2026,
            'month' => 10,
            'created_by' => $admin->id,
            'status' => 'draft',
        ]);
        $previousSchedule->availabilities()->create([
            'user_id' => $volunteer->id,
            'weekdays' => [2, 5],
            'date_overrides' => [],
            'participates' => true,
            'monthly_turns' => 3,
        ]);
        $this->travelTo(Carbon::parse('2026-10-31 23:50:00'));

        $this->artisan('turnos:crear-borrador-siguiente')
            ->expectsOutputToContain('noviembre 2026')
            ->assertExitCode(0);

        $schedule = TurnoSchedule::query()->where('year', 2026)->where('month', 11)->firstOrFail();
        $availability = $schedule->availabilities()->where('user_id', $volunteer->id)->firstOrFail();
        $this->assertSame(30, $schedule->days()->count());
        $this->assertSame([2, 5], $availability->weekdays);
        $this->assertTrue($availability->participates);
        $this->assertSame(3, $availability->monthly_turns);
        $this->assertSame(3, DB::table('turno_asignaciones')
            ->join('turno_days', 'turno_days.id', '=', 'turno_asignaciones.turno_dia_id')
            ->where('turno_days.turno_schedule_id', $schedule->id)
            ->where('turno_asignaciones.user_id', $volunteer->id)
            ->count());

        $this->artisan('turnos:crear-borrador-siguiente')
            ->expectsOutputToContain('ya existía')
            ->assertExitCode(0);
        $this->assertSame(1, TurnoSchedule::query()->where('year', 2026)->where('month', 11)->count());
    }

    public function test_scheduler_builds_a_fair_draft_and_admin_can_publish_it(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteers = collect(range(1, 3))->map(fn () => User::factory()->create(['role' => User::ROLE_VOLUNTARIO]));
        $schedule = $this->createSchedule($admin);

        foreach ($volunteers as $volunteer) {
            TurnoAvailability::create([
                'turno_schedule_id' => $schedule->id,
                'user_id' => $volunteer->id,
                'weekdays' => [1, 2, 3, 4, 5],
                'date_overrides' => [],
                'participates' => true,
                'monthly_turns' => 2,
            ]);
        }

        $result = app(TurnoScheduler::class)->generate($schedule);
        $this->assertSame(0, $result['underassigned_volunteers']);

        $volunteerCounts = DB::table('turno_asignaciones')
            ->whereIn('turno_dia_id', $schedule->days()->select('id'))
            ->selectRaw('COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total');
        $this->assertSame([2, 2, 2], $volunteerCounts->sort()->values()->all());

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

    public function test_calendar_does_not_publish_when_a_participant_lacks_monthly_turns(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $schedule = $this->createSchedule($admin);
        TurnoAvailability::create([
            'turno_schedule_id' => $schedule->id,
            'user_id' => $volunteer->id,
            'weekdays' => [1],
            'date_overrides' => [],
            'participates' => true,
            'monthly_turns' => 2,
        ]);
        $schedule->days()->whereDate('date', '!=', '2026-11-02')->update(['is_open' => false]);

        $result = app(TurnoScheduler::class)->generate($schedule);
        $this->assertSame(1, $result['underassigned_volunteers']);
        $this->actingAs($admin)->post(route('admin.turnos.publicar', $schedule))
            ->assertSessionHasErrors('publicar');
        $this->assertDatabaseHas('turno_schedules', ['id' => $schedule->id, 'status' => 'draft']);
    }

    public function test_date_exceptions_override_weekday_preferences_during_generation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $volunteer = User::factory()->create(['role' => User::ROLE_VOLUNTARIO]);
        $schedule = $this->createSchedule($admin);
        $schedule->days()
            ->whereDate('date', '!=', '2026-11-02')
            ->whereDate('date', '!=', '2026-11-03')
            ->update(['is_open' => false]);
        TurnoAvailability::create([
            'turno_schedule_id' => $schedule->id,
            'user_id' => $volunteer->id,
            'weekdays' => [1],
            'date_overrides' => [
                '2026-11-02' => 'unavailable',
                '2026-11-03' => 'available',
            ],
            'participates' => true,
            'monthly_turns' => 1,
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
            ->assertSee('Reportes')
            ->assertSee('Reportes de la biblioteca')
            ->assertSee('Ingresar un reporte')
            ->assertSee('Luz quemada');
        $this->actingAs($admin)->post(route('admin.reportes.store'), [
            'title' => 'Estante por reparar',
            'category' => 'Mantención',
            'description' => 'El estante de la entrada está inestable.',
        ])->assertRedirect(route('admin.reportes.index'));

        $adminReport = VolunteerReport::query()->where('title', 'Estante por reparar')->firstOrFail();
        $this->assertSame($admin->id, $adminReport->reported_by);

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
        $this->put(route('admin.turnos.voluntarios.update', $schedule), [
            'volunteers' => [
                $volunteer->id => [
                    'enabled' => 1,
                    'participates' => 1,
                    'weekdays' => [1],
                    'monthly_turns' => 2,
                ],
            ],
        ])->assertForbidden();
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
