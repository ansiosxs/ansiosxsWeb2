<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TurnoActivity;
use App\Models\TurnoAvailability;
use App\Models\TurnoDay;
use App\Models\TurnoSchedule;
use App\Models\User;
use App\Services\TurnoScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
                ->withCount(['days', 'availabilities'])
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
            'volunteers_per_day' => ['required', 'integer', 'between:1,255'],
            'availability_closes_at' => ['nullable', 'date'],
        ]);

        $schedule = DB::transaction(function () use ($data, $request) {
            $schedule = TurnoSchedule::create([
                ...$data,
                'created_by' => $request->user()->id,
                'status' => 'draft',
            ]);

            $date = Carbon::create($schedule->year, $schedule->month, 1)->startOfMonth();
            $lastDay = $date->copy()->endOfMonth();
            $days = [];

            while ($date->lte($lastDay)) {
                $days[] = ['date' => $date->toDateString(), 'is_open' => $date->isWeekday()];
                $date->addDay();
            }

            $schedule->days()->createMany($days);

            return $schedule;
        });

        return redirect()->route('admin.turnos.show', $schedule)->with('status', 'Calendario mensual creado. Ya se puede recibir disponibilidad.');
    }

    public function show(TurnoSchedule $turno): View
    {
        $turno->load(['days.volunteers', 'activities']);
        $user = auth()->user();
        $availability = $turno->availabilities()->where('user_id', $user->id)->first();
        $availabilityResponses = $user->isAdmin()
            ? $turno->availabilities()->with('volunteer')->get()
            : collect();
        $canSubmitAvailability = $turno->status === 'draft'
            && (! $turno->availability_closes_at || $turno->availability_closes_at->isFuture());
        $volunteers = User::query()->where('role', User::ROLE_VOLUNTARIO)->where('activo', true)->orderBy('name')->get();

        return view('admin.turnos.show', [
            'turno' => $turno,
            'disponibilidad' => $availability,
            'puedeResponder' => $canSubmitAvailability,
            'voluntarios' => $volunteers,
            'disponibilidades' => $availabilityResponses,
            'actividadesPorFecha' => $turno->activities->groupBy(fn (TurnoActivity $activity) => $activity->date->toDateString()),
        ]);
    }

    public function disponibilidad(Request $request, TurnoSchedule $turno): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isVolunteer() && $user->activo, 403);
        abort_unless($turno->status === 'draft', 403, 'Este calendario ya no recibe disponibilidad.');
        abort_if($turno->availability_closes_at?->isPast(), 403, 'El plazo para indicar disponibilidad ya cerró.');

        $data = $request->validate([
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['required', 'integer', 'between:1,7'],
            'date_overrides' => ['nullable', 'array'],
        ]);

        $overrides = [];
        foreach (($data['date_overrides'] ?? []) as $date => $value) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)
                || ! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))
                || (int) substr($date, 0, 4) !== $turno->year
                || (int) substr($date, 5, 2) !== $turno->month
                || ! in_array($value, ['default', 'available', 'unavailable'], true)) {
                throw ValidationException::withMessages(['date_overrides' => 'Una de las excepciones no corresponde a una fecha válida de este mes.']);
            }

            if ($value !== 'default') {
                $overrides[$date] = $value;
            }
        }

        TurnoAvailability::query()->updateOrCreate(
            ['turno_schedule_id' => $turno->id, 'user_id' => $user->id],
            [
                'weekdays' => array_values(array_unique(array_map('intval', $data['weekdays'] ?? []))),
                'date_overrides' => $overrides,
            ],
        );

        return redirect()->route('admin.turnos.show', $turno)->with('status', 'Tu disponibilidad quedó guardada.');
    }

    public function generar(TurnoSchedule $turno, TurnoScheduler $scheduler): RedirectResponse
    {
        $this->requireAdmin();
        $result = $scheduler->generate($turno);

        if ($result['understaffed_days'] > 0) {
            return redirect()->route('admin.turnos.show', $turno)
                ->with('warning', "Se generó una propuesta, pero {$result['understaffed_days']} días no alcanzan la cobertura requerida. Ajusta manualmente o cambia el mínimo antes de publicar.");
        }

        return redirect()->route('admin.turnos.show', $turno)
            ->with('status', "Propuesta generada: {$result['assigned']} asignaciones distribuidas según disponibilidad y equidad mensual.");
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

        $understaffed = $turno->days()
            ->where('is_open', true)
            ->withCount('volunteers')
            ->get()
            ->filter(fn (TurnoDay $day) => $day->volunteers_count < $turno->volunteers_per_day);

        if ($understaffed->isNotEmpty()) {
            return redirect()->route('admin.turnos.show', $turno)
                ->withErrors(['publicar' => 'No se puede publicar: hay días abiertos bajo la cobertura mínima. Cierra esos días o asigna suficientes voluntarios.']);
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
