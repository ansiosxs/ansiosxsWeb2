<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TurnoActivity;
use App\Models\TurnoAvailability;
use App\Models\TurnoDay;
use App\Models\TurnoSchedule;
use App\Models\TurnoVolunteerProfile;
use App\Models\User;
use App\Services\TurnoScheduleFactory;
use App\Services\TurnoScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TurnoController extends Controller
{
    public function index(): View
    {
        return view('admin.turnos.index', [
            'turnos' => TurnoSchedule::query()
                ->withCount([
                    'days',
                    'availabilities' => fn ($query) => $query->where('participates', true),
                ])
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->paginate(24),
        ]);
    }

    public function create(): View
    {
        $this->requireAdmin();

        return view('admin.turnos.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireAdmin();

        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2020,2100'],
            'month' => [
                'required',
                'integer',
                'between:1,12',
                Rule::unique('turno_schedules', 'month')->where(fn ($query) => $query->where('year', (int) $request->input('year'))),
            ],
        ]);

        [$schedule] = app(TurnoScheduleFactory::class)->create(
            (int) $data['year'],
            (int) $data['month'],
            $request->user(),
        );

        return redirect()->route('admin.turnos.show', $schedule)->with('status', 'Borrador mensual creado. Revisa participantes y preferencias antes de generar la propuesta.');
    }

    public function show(TurnoSchedule $turno): View
    {
        $turno->load(['days.volunteers', 'activities', 'availabilities.volunteer']);
        $volunteers = User::query()->where('role', User::ROLE_VOLUNTARIO)->where('activo', true)->orderBy('name')->get();
        $profiles = TurnoVolunteerProfile::query()
            ->whereIn('user_id', $volunteers->pluck('id'))
            ->get()
            ->keyBy('user_id');

        return view('admin.turnos.show', [
            'turno' => $turno,
            'voluntarios' => $volunteers,
            'perfilesVoluntarios' => $profiles,
            'disponibilidades' => $turno->availabilities->keyBy('user_id'),
            'actividadesPorFecha' => $turno->activities->groupBy(fn (TurnoActivity $activity) => $activity->date->toDateString()),
        ]);
    }

    public function actualizarVoluntarios(Request $request, TurnoSchedule $turno): RedirectResponse
    {
        $this->requireAdmin();
        abort_unless($turno->status === 'draft', 403, 'No se pueden cambiar los participantes de un calendario publicado.');

        $data = $request->validate([
            'volunteers' => ['nullable', 'array'],
            'volunteers.*.enabled' => ['required', 'boolean'],
            'volunteers.*.participates' => ['required', 'boolean'],
            'volunteers.*.weekdays' => ['nullable', 'array'],
            'volunteers.*.weekdays.*' => ['required', 'integer', 'between:1,5'],
            'volunteers.*.monthly_turns' => ['required', 'integer', 'between:0,31'],
        ]);

        $activeVolunteers = User::query()
            ->where('role', User::ROLE_VOLUNTARIO)
            ->where('activo', true)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($data, $activeVolunteers, $turno) {
            foreach ($data['volunteers'] ?? [] as $userId => $volunteerData) {
                $volunteer = $activeVolunteers->get((int) $userId);
                abort_unless($volunteer, 422, 'La lista contiene una cuenta que no es voluntaria activa.');
                $weekdays = array_values(array_unique(array_map('intval', $volunteerData['weekdays'] ?? [])));
                $enabled = (bool) $volunteerData['enabled'];
                $participates = $enabled && (bool) $volunteerData['participates'];

                TurnoVolunteerProfile::query()->updateOrCreate(
                    ['user_id' => $volunteer->id],
                    [
                        'enabled' => $enabled,
                        'weekdays' => $weekdays,
                        'monthly_turns' => (int) $volunteerData['monthly_turns'],
                    ],
                );

                TurnoAvailability::query()->updateOrCreate(
                    ['turno_schedule_id' => $turno->id, 'user_id' => $volunteer->id],
                    [
                        'weekdays' => $weekdays,
                        'date_overrides' => [],
                        'participates' => $participates,
                        'monthly_turns' => (int) $volunteerData['monthly_turns'],
                    ],
                );
            }
        });

        return redirect()->route('admin.turnos.show', $turno)->with('status', 'La lista de voluntarios, sus preferencias y su participación mensual quedaron guardadas.');
    }

    public function generar(TurnoSchedule $turno, TurnoScheduler $scheduler): RedirectResponse
    {
        $this->requireAdmin();
        $result = $scheduler->generate($turno);

        if ($result['underassigned_volunteers'] > 0) {
            return redirect()->route('admin.turnos.show', $turno)
                ->with('warning', "Se generó una propuesta, pero {$result['underassigned_volunteers']} voluntarios no tienen suficientes fechas disponibles para completar sus turnos del mes. Revisa sus preferencias o ajusta la asignación manualmente.");
        }

        return redirect()->route('admin.turnos.show', $turno)
            ->with('status', "Propuesta generada: {$result['assigned']} turnos asignados según las preferencias y cantidades mensuales.");
    }

    public function actualizarDia(Request $request, TurnoSchedule $turno, TurnoDay $dia): RedirectResponse
    {
        $this->requireAdmin();
        abort_unless($turno->status === 'draft' && $dia->turno_schedule_id === $turno->id, 404);

        $data = $request->validate([
            'is_open' => ['required', 'boolean'],
            'assignment_notes' => ['nullable', 'string', 'max:5000'],
            'volunteers' => ['nullable', 'array'],
            'volunteers.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_VOLUNTARIO)->where('activo', true)),
            ],
        ]);

        $dia->update([
            'is_open' => (bool) $data['is_open'],
            'assignment_notes' => $data['assignment_notes'] ?? null,
        ]);
        $dia->volunteers()->sync(
            $dia->is_open
                ? collect($data['volunteers'] ?? [])->mapWithKeys(fn ($id) => [(int) $id => ['assigned_by' => $request->user()->id]])->all()
                : [],
        );

        return redirect()->route('admin.turnos.show', $turno)->with('status', 'Se actualizó el turno.');
    }

    public function publicar(TurnoSchedule $turno): RedirectResponse
    {
        $this->requireAdmin();
        abort_unless($turno->status === 'draft', 403, 'El calendario ya está publicado.');

        $assignedCounts = DB::table('turno_asignaciones')
            ->join('turno_days', 'turno_days.id', '=', 'turno_asignaciones.turno_dia_id')
            ->where('turno_days.turno_schedule_id', $turno->id)
            ->select('turno_asignaciones.user_id')
            ->selectRaw('COUNT(*) as assigned_count')
            ->groupBy('turno_asignaciones.user_id')
            ->pluck('assigned_count', 'user_id');
        $underassigned = $turno->availabilities()
            ->where('participates', true)
            ->whereRaw('monthly_turns > ?', [0])
            ->get()
            ->filter(fn (TurnoAvailability $availability) => $assignedCounts->get($availability->user_id, 0) < $availability->monthly_turns);

        if ($underassigned->isNotEmpty()) {
            return redirect()->route('admin.turnos.show', $turno)
                ->withErrors(['publicar' => 'No se puede publicar: hay voluntarios participantes con menos turnos que los solicitados. Ajusta la cantidad, las preferencias o sus asignaciones.']);
        }

        $turno->update(['status' => 'published', 'published_at' => now()]);

        return redirect()->route('admin.turnos.show', $turno)->with('status', 'Calendario publicado. Los voluntarios ya pueden consultarlo y descargar la imagen.');
    }

    public function guardarActividad(Request $request, TurnoSchedule $turno): RedirectResponse
    {
        $this->requireAdmin();
        abort_unless($turno->status === 'draft', 403, 'No se pueden cambiar actividades de un calendario publicado.');

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'title' => ['required', 'string', 'max:160'],
            'responsible' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'convocatoria' => ['nullable', 'string', 'max:5000'],
        ]);
        $this->assertScheduleDate($turno, $data['date']);

        $turno->activities()->create([...$data, 'created_by' => $request->user()->id]);

        return redirect()->route('admin.turnos.show', $turno)->with('status', 'Actividad agregada al calendario.');
    }

    public function actualizarActividad(Request $request, TurnoSchedule $turno, TurnoActivity $actividad): RedirectResponse
    {
        $this->requireAdmin();
        abort_unless($turno->status === 'draft' && $actividad->turno_schedule_id === $turno->id, 404);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'title' => ['required', 'string', 'max:160'],
            'responsible' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'convocatoria' => ['nullable', 'string', 'max:5000'],
        ]);
        $this->assertScheduleDate($turno, $data['date']);
        $actividad->update($data);

        return redirect()->route('admin.turnos.show', $turno)->with('status', 'Actividad actualizada.');
    }

    public function eliminarActividad(TurnoSchedule $turno, TurnoActivity $actividad): RedirectResponse
    {
        $this->requireAdmin();
        abort_unless($turno->status === 'draft' && $actividad->turno_schedule_id === $turno->id, 404);
        $actividad->delete();

        return redirect()->route('admin.turnos.show', $turno)->with('status', 'Actividad eliminada.');
    }

    public function calendarioSvg(TurnoSchedule $turno): Response
    {
        abort_unless($turno->status === 'published' || auth()->user()?->isAdmin(), 403);
        $turno->load(['days.volunteers', 'activities']);

        return response()
            ->view('admin.turnos.calendario-svg', [
                'turno' => $turno,
                'dias' => $turno->days->keyBy(fn (TurnoDay $day) => $day->date->toDateString()),
                'actividadesPorFecha' => $turno->activities->groupBy(fn (TurnoActivity $activity) => $activity->date->toDateString()),
                'logoData' => base64_encode(file_get_contents(public_path('logo.png'))),
                'mascotaData' => base64_encode(file_get_contents(public_path('mascotas/serpi.png'))),
            ])
            ->header('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->header('Cache-Control', 'private, no-store');
    }

    private function assertScheduleDate(TurnoSchedule $schedule, string $date): void
    {
        $valid = checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))
            && (int) substr($date, 0, 4) === $schedule->year
            && (int) substr($date, 5, 2) === $schedule->month;

        if (! $valid) {
            throw ValidationException::withMessages(['date' => 'La fecha debe pertenecer al mes del calendario.']);
        }
    }

    private function requireAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
