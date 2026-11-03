@extends('layouts.admin')
@section('title', ucfirst($turno->month_label))
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Calendario de voluntariado</p><h2>{{ ucfirst($turno->month_label) }}</h2><p class="muted">{{ $turno->status === 'published' ? 'Publicado' : 'Borrador · Asignación por preferencias y cantidad mensual' }}</p></div>
    <div class="inline-form">
        @if ($turno->status === 'published' || auth()->user()->isAdmin())
            <button type="button" class="button button-secondary" data-calendar-png data-svg-url="{{ route('admin.turnos.calendario', $turno) }}" data-calendar-filename="turnos-{{ $turno->year }}-{{ sprintf('%02d', $turno->month) }}.png">Descargar PNG para WhatsApp</button>
        @endif
        <a href="{{ route('admin.turnos.index') }}">Volver a calendarios</a>
    </div>
</div>
@if (session('warning'))<div class="notice notice-error" role="status">{{ session('warning') }}</div>@endif

@if ($errors->has('publicar'))
    <div class="notice notice-error" role="alert">{{ $errors->first('publicar') }}</div>
@endif

@if (auth()->user()->isAdmin() && $turno->status === 'draft')
    <section class="panel">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h3>Voluntarios que pueden abrir</h3>
            <a class="button button-secondary" href="{{ route('admin.usuarios.create') }}">Agregar voluntario</a>
        </div>
        <p>Activa a quienes pueden tomar turnos, marca si participan en {{ $turno->month_label }} y define sus días preferidos y cantidad de turnos (2 por defecto). La participación del mes anterior se copia automáticamente.</p>
        <form method="post" action="{{ route('admin.turnos.voluntarios.update', $turno) }}">
            @csrf @method('put')
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Nombre</th><th>Puede abrir</th><th>Participa este mes</th><th>Días preferidos</th><th>Turnos al mes</th><th>Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($voluntarios as $voluntario)
                        @php
                            $perfil = $perfilesVoluntarios->get($voluntario->id);
                            $disponibilidad = $disponibilidades->get($voluntario->id);
                            $weekdays = old("volunteers.{$voluntario->id}.weekdays", $disponibilidad?->weekdays ?? $perfil?->weekdays ?? []);
                            $enabled = old("volunteers.{$voluntario->id}.enabled", $perfil?->enabled ?? false);
                            $participates = old("volunteers.{$voluntario->id}.participates", $disponibilidad?->participates ?? false);
                            $monthlyTurns = old("volunteers.{$voluntario->id}.monthly_turns", $disponibilidad?->monthly_turns ?? $perfil?->monthly_turns ?? 2);
                        @endphp
                        <tr>
                            <th scope="row">{{ $voluntario->name }}</th>
                            <td><label class="inline-form"><input type="hidden" name="volunteers[{{ $voluntario->id }}][enabled]" value="0"><input type="checkbox" name="volunteers[{{ $voluntario->id }}][enabled]" value="1" @checked($enabled)> Habilitado</label></td>
                            <td><label class="inline-form"><input type="hidden" name="volunteers[{{ $voluntario->id }}][participates]" value="0"><input type="checkbox" name="volunteers[{{ $voluntario->id }}][participates]" value="1" @checked($participates)> Sí</label></td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ([1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie'] as $weekday => $label)
                                        <label class="inline-form"><input type="checkbox" name="volunteers[{{ $voluntario->id }}][weekdays][]" value="{{ $weekday }}" @checked(in_array($weekday, $weekdays))>{{ $label }}</label>
                                    @endforeach
                                </div>
                            </td>
                            <td><input aria-label="Turnos mensuales de {{ $voluntario->name }}" type="number" name="volunteers[{{ $voluntario->id }}][monthly_turns]" min="0" max="31" value="{{ $monthlyTurns }}" required></td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a class="button button-secondary" href="{{ route('admin.usuarios.edit', $voluntario) }}">Editar</a>
                                    <button class="button button-danger" type="submit" form="eliminar-voluntario-{{ $voluntario->id }}">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button class="button button-secondary mt-4" type="submit">Guardar lista y preferencias</button>
        </form>
        @foreach ($voluntarios as $voluntario)
            <form id="eliminar-voluntario-{{ $voluntario->id }}" method="post" action="{{ route('admin.usuarios.destroy', $voluntario) }}" data-confirm="¿Eliminar permanentemente la cuenta de {{ $voluntario->name }}? Solo se podrá eliminar si no tiene historial asociado.">
                @csrf @method('delete')
            </form>
        @endforeach
        <div class="flex flex-wrap items-center justify-between gap-3 mt-6">
            <div><h3>Preparar y publicar</h3><p class="muted">La propuesta asigna a cada participante la cantidad mensual indicada en sus días preferidos.</p></div>
            <div class="inline-form">
                <form method="post" action="{{ route('admin.turnos.generar', $turno) }}" data-confirm="Esto reemplazará las asignaciones actuales por una nueva propuesta. ¿Continuar?">@csrf<button class="button button-secondary" type="submit">Generar borrador de asignaciones</button></form>
                <form method="post" action="{{ route('admin.turnos.publicar', $turno) }}" data-confirm="¿Publicar el calendario para todos los voluntarios?">@csrf<button class="button" type="submit">Publicar calendario</button></form>
            </div>
        </div>
    </section>
@endif

<section class="panel">
    @php
        $monthStart = \Illuminate\Support\Carbon::create($turno->year, $turno->month, 1);
        $calendarStart = $monthStart->copy()->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
        $calendarEnd = $monthStart->copy()->endOfMonth()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY);
        $daysByDate = $turno->days->keyBy(fn ($day) => $day->date->toDateString());
        $calendarWeeks = [];
        $calendarCursor = $calendarStart->copy();
        while ($calendarCursor->lte($calendarEnd)) {
            $week = [];
            for ($weekday = 0; $weekday < 7; $weekday++) {
                $week[] = $calendarCursor->copy();
                $calendarCursor->addDay();
            }
            $calendarWeeks[] = $week;
        }
        $openDaysCount = $turno->days->where('is_open', true)->count();
        $assignedShiftsCount = $turno->days->sum(fn ($day) => $day->volunteers->count());
        $participatingVolunteersCount = $turno->availabilities->where('participates', true)->count();
        $underassignedVolunteers = $turno->availabilities
            ->where('participates', true)
            ->filter(fn ($availability) => $turno->days->sum(
                fn ($day) => $day->volunteers->contains('id', $availability->user_id) ? 1 : 0,
            ) < $availability->monthly_turns);
        $pendingTurnsCount = $underassignedVolunteers->sum(fn ($availability) => max(
            0,
            $availability->monthly_turns - $turno->days->sum(
                fn ($day) => $day->volunteers->contains('id', $availability->user_id) ? 1 : 0,
            ),
        ));
    @endphp
    <div class="turno-sheet-heading">
        <div>
            <p class="eyebrow">Vista de planilla</p>
            <h3>Arma el calendario fecha por fecha</h3>
            <p class="muted mb-0">Cada columna es un día de la semana. Revisa los turnos y actividades de un vistazo; edita una celda para asignar personas, abrir o cerrar la biblioteca y dejar notas.</p>
        </div>
        <div class="turno-sheet-legend" aria-label="Leyenda del calendario">
            <span><i class="turno-legend-dot is-open"></i>Abierto</span>
            <span><i class="turno-legend-dot is-closed"></i>Cerrado</span>
            <span><i class="turno-legend-dot is-activity"></i>Actividad o nota</span>
        </div>
    </div>
    <div class="turno-sheet-stats" aria-label="Resumen del calendario">
        <div class="turno-sheet-stat"><span>Días abiertos</span><strong>{{ $openDaysCount }}</strong><small>de {{ $turno->days->count() }} fechas</small></div>
        <div class="turno-sheet-stat"><span>Turnos asignados</span><strong>{{ $assignedShiftsCount }}</strong><small>total de personas asignadas este mes</small></div>
        <div class="turno-sheet-stat"><span>Actividades</span><strong>{{ $turno->activities->count() }}</strong><small>en el calendario mensual</small></div>
        <div @class(['turno-sheet-stat', 'is-alert' => $pendingTurnsCount > 0])><span>Turnos por completar</span><strong>{{ $pendingTurnsCount }}</strong><small>{{ $participatingVolunteersCount === 0 ? 'sin participantes marcados' : ($underassignedVolunteers->count() ? $underassignedVolunteers->count().' voluntarios pendientes' : 'cantidades mensuales completas') }}</small></div>
    </div>
    @if ($pendingTurnsCount > 0 && auth()->user()->isAdmin())
        <p class="turno-sheet-alert" role="status">Faltan {{ $pendingTurnsCount }} turnos para completar las cantidades mensuales; puedes revisar las preferencias o hacer ajustes manuales.</p>
    @endif
    <div class="turno-sheet-scroll" role="region" aria-label="Planilla mensual de turnos; desplázate horizontalmente para ver todos los días" tabindex="0">
        <table class="turno-sheet">
            <thead>
                <tr>
                    <th scope="col" class="turno-sheet-week-number">Sem.</th>
                    @foreach (['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $weekday)
                        <th scope="col">{{ $weekday }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($calendarWeeks as $weekIndex => $week)
                    <tr>
                        <th scope="row" class="turno-sheet-week-number">{{ sprintf('%02d', $weekIndex + 1) }}</th>
                        @foreach ($week as $calendarDate)
                            @php
                                $dateKey = $calendarDate->toDateString();
                                $dia = $daysByDate->get($dateKey);
                                $isInMonth = $calendarDate->month === $turno->month;
                                $dayActivities = $isInMonth ? $turno->activities->filter(fn ($activity) => $activity->date->isSameDay($calendarDate)) : collect();
                            @endphp
                            @if ($isInMonth && $dia)
                                <td @class(['turno-sheet-cell', 'is-open' => $dia->is_open, 'is-closed' => ! $dia->is_open, 'is-weekend' => $calendarDate->isWeekend()])>
                                    <article class="turno-day-card">
                                        <div class="turno-day-heading">
                                            <span class="turno-day-number">{{ $calendarDate->format('j') }}</span>
                                            <span @class(['turno-day-state', 'is-open' => $dia->is_open, 'is-closed' => ! $dia->is_open])>{{ $dia->is_open ? 'Abierto' : 'Cerrado' }}</span>
                                        </div>
                                        @if ($dia->is_open)
                                            <div class="turno-coverage">{{ $dia->volunteers->count() }} {{ $dia->volunteers->count() === 1 ? 'persona asignada' : 'personas asignadas' }}</div>
                                        @endif
                                        <div class="turno-day-people">
                                            @forelse ($dia->volunteers as $volunteer)
                                                <span class="turno-person-chip" title="{{ $volunteer->name }}">{{ $volunteer->name }}</span>
                                            @empty
                                                @if ($dia->is_open)
                                                    <span class="turno-day-empty">Sin asignar</span>
                                                @else
                                                    <span class="turno-day-empty">Sin turno</span>
                                                @endif
                                            @endforelse
                                        </div>
                                        @if ($dia->assignment_notes)
                                            <p class="turno-day-note">{{ \Illuminate\Support\Str::limit($dia->assignment_notes, 92) }}</p>
                                        @endif
                                        @foreach ($dayActivities as $activity)
                                            <span class="turno-activity-chip" title="{{ $activity->title }}">{{ \Illuminate\Support\Str::limit($activity->title, 48) }}</span>
                                        @endforeach
                                        @if (auth()->user()->isAdmin() && $turno->status === 'draft')
                                            <details class="turno-cell-editor">
                                                <summary>Editar fecha</summary>
                                                <form method="post" action="{{ route('admin.turnos.dias.update', [$turno, $dia]) }}" class="form-stack">
                                                    @csrf @method('put')
                                                    <label class="turno-open-toggle"><input type="hidden" name="is_open" value="0"><input type="checkbox" name="is_open" value="1" @checked($dia->is_open)> Biblioteca abierta</label>
                                                    <label>Personas asignadas
                                                        <select name="volunteers[]" multiple size="5" aria-label="Selecciona las personas asignadas a esta fecha">
                                                            @foreach ($voluntarios as $voluntario)
                                                                <option value="{{ $voluntario->id }}" @selected($dia->volunteers->contains('id', $voluntario->id))>{{ $voluntario->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <small>Usa Ctrl o Cmd para elegir más de una persona.</small>
                                                    </label>
                                                    <label>Nota o actividad<textarea name="assignment_notes" rows="3" maxlength="5000" placeholder="Ej.: turno, taller, feria o información importante">{{ $dia->assignment_notes }}</textarea></label>
                                                    <button class="button button-secondary" type="submit">Guardar fecha</button>
                                                </form>
                                            </details>
                                        @endif
                                    </article>
                                </td>
                            @else
                                <td class="turno-sheet-cell is-outside" aria-hidden="true"></td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="turno-sheet-footnote">Las actividades, notas y personas asignadas se muestran en su fecha correspondiente. Administración puede ajustar manualmente las asignaciones antes de publicar.</p>
</section>

@if (auth()->user()->isVolunteer())
    <section class="panel">
        <h3>Actividades del mes</h3>
        @forelse ($turno->activities as $actividad)
            <article class="mb-4 rounded-xl border-2 border-[#e7e0d8] p-4">
                <p class="eyebrow">{{ $actividad->date->translatedFormat('l j F') }}</p>
                <h3>{{ $actividad->title }}</h3>
                @if ($actividad->responsible)<p class="muted">Responsable: {{ $actividad->responsible }}</p>@endif
                <p class="whitespace-pre-line">{{ $actividad->description }}</p>
                @if ($actividad->convocatoria)<p class="mb-0"><strong>Convocatoria:</strong> {{ $actividad->convocatoria }}</p>@endif
            </article>
        @empty
            <p>No hay actividades registradas para este mes.</p>
        @endforelse
    </section>
@endif

@if (auth()->user()->isAdmin())
    <section class="panel">
        <h3>Actividades</h3>
        @if ($turno->status === 'draft')
            <form class="form-stack mb-6" method="post" action="{{ route('admin.turnos.actividades.store', $turno) }}">
                @csrf
                <div class="grid-two">
                    <label>Fecha *<select name="date" required>@foreach ($turno->days as $dia)<option value="{{ $dia->date->toDateString() }}">{{ $dia->date->format('d/m/Y') }}</option>@endforeach</select></label>
                    <label>Título *<input name="title" maxlength="160" required></label>
                </div>
                <label>Persona responsable (opcional)<input name="responsible" maxlength="255"></label>
                <label>Descripción *<textarea name="description" rows="3" required></textarea></label>
                <label>Texto o nota de convocatoria (opcional)<textarea name="convocatoria" rows="2"></textarea></label>
                <button class="button" type="submit">Agregar actividad</button>
            </form>
            @foreach ($turno->activities as $actividad)
                <form class="form-stack mb-6 rounded-xl border-2 border-[#e7e0d8] p-4" method="post" action="{{ route('admin.turnos.actividades.update', [$turno, $actividad]) }}">
                    @csrf @method('put')
                    <div class="grid-two">
                        <label>Fecha *<input type="date" name="date" value="{{ $actividad->date->toDateString() }}" required></label>
                        <label>Título *<input name="title" value="{{ $actividad->title }}" maxlength="160" required></label>
                    </div>
                    <label>Persona responsable<input name="responsible" value="{{ $actividad->responsible }}" maxlength="255"></label>
                    <label>Descripción *<textarea name="description" rows="2" required>{{ $actividad->description }}</textarea></label>
                    <label>Texto o nota de convocatoria<textarea name="convocatoria" rows="2">{{ $actividad->convocatoria }}</textarea></label>
                    <div class="inline-form"><button class="button button-secondary" type="submit">Guardar actividad</button></div>
                </form>
                <form method="post" action="{{ route('admin.turnos.actividades.destroy', [$turno, $actividad]) }}" class="mb-4" data-confirm="¿Eliminar esta actividad?">
                    @csrf @method('delete')<button class="button button-danger" type="submit">Eliminar actividad</button>
                </form>
            @endforeach
        @else
            @forelse ($turno->activities as $actividad)
                <p><strong>{{ $actividad->date->format('d/m') }} · {{ $actividad->title }}</strong>@if ($actividad->responsible)<br><span class="muted">Responsable: {{ $actividad->responsible }}</span>@endif<br>{{ $actividad->description }}@if ($actividad->convocatoria)<br><span class="muted">Convocatoria: {{ $actividad->convocatoria }}</span>@endif</p>
            @empty<p>No hay actividades registradas.</p>@endforelse
        @endif
    </section>
@endif
@endsection
