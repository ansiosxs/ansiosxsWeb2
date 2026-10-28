@extends('layouts.admin')
@section('title', ucfirst($turno->month_label))
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Calendario de voluntariado</p><h2>{{ ucfirst($turno->month_label) }}</h2><p class="muted">{{ $turno->status === 'published' ? 'Publicado' : 'Borrador visible para voluntarios' }} · Cobertura mínima: {{ $turno->volunteers_per_day }}</p></div>
    <div class="inline-form">
        @if ($turno->status === 'published' || auth()->user()->isAdmin())
            <button type="button" class="button button-secondary" data-calendar-png data-svg-url="{{ route('admin.turnos.calendario', $turno) }}" data-calendar-filename="turnos-{{ $turno->year }}-{{ sprintf('%02d', $turno->month) }}.png">Descargar PNG para WhatsApp</button>
        @endif
        <a href="{{ route('admin.turnos.index') }}">Volver a calendarios</a>
    </div>
</div>
@if (session('warning'))<div class="notice notice-error" role="status">{{ session('warning') }}</div>@endif

@if (auth()->user()->isVolunteer() && $puedeResponder)
    <section class="panel">
        <h3>Indica tu disponibilidad</h3>
        <p>Marca los días de semana en que normalmente puedes venir. Usa las excepciones para agregar o quitar fechas concretas. Solo el administrador puede ver tus preferencias individuales.</p>
        <form class="form-stack" method="post" action="{{ route('admin.turnos.disponibilidad', $turno) }}">
            @csrf
            <fieldset>
                <legend class="font-bold">Días habituales</legend>
                <div class="flex flex-wrap gap-4">
                    @foreach ([1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'] as $weekday => $label)
                        <label class="inline-form"><input type="checkbox" name="weekdays[]" value="{{ $weekday }}" @checked(in_array($weekday, old('weekdays', $disponibilidad?->weekdays ?? [])))>{{ $label }}</label>
                    @endforeach
                </div>
            </fieldset>
            <details>
                <summary class="font-bold">Excepciones por fecha</summary>
                <p class="muted mt-2">Cambia solo las fechas que no coinciden con tus días habituales; también puedes marcar fines de semana.</p>
                <div class="grid-two">
                    @php
                        $dateOverrides = old('date_overrides', $disponibilidad?->date_overrides ?? []);
                    @endphp
                    @foreach ($turno->days as $dia)
                        @php
                            $dateKey = $dia->date->toDateString();
                        @endphp
                        <label>{{ $dia->date->translatedFormat('l j') }}
                            <select name="date_overrides[{{ $dateKey }}]">
                                <option value="default" @selected(($dateOverrides[$dateKey] ?? 'default') === 'default')>Según mis días habituales</option>
                                <option value="available" @selected(($dateOverrides[$dateKey] ?? '') === 'available')>Sí puedo</option>
                                <option value="unavailable" @selected(($dateOverrides[$dateKey] ?? '') === 'unavailable')>No puedo</option>
                            </select>
                        </label>
                    @endforeach
                </div>
            </details>
            <button class="button" type="submit">Guardar disponibilidad</button>
        </form>
    </section>
@elseif (auth()->user()->isVolunteer() && $turno->status === 'draft')
    <section class="panel"><p>El plazo para responder disponibilidad ya cerró. Consulta al administrador si necesitas actualizarla.</p></section>
@endif

@if (auth()->user()->isAdmin() && $turno->status === 'draft')
    <section class="panel">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h3>Preparar y publicar</h3><p class="muted">Disponibilidades recibidas: {{ $disponibilidades->count() }}. La propuesta equilibra los turnos y respeta los días marcados.</p></div>
            <div class="inline-form">
                <form method="post" action="{{ route('admin.turnos.generar', $turno) }}" data-confirm="Esto reemplazará las asignaciones actuales por una nueva propuesta. ¿Continuar?">@csrf<button class="button button-secondary" type="submit">Generar propuesta</button></form>
                <form method="post" action="{{ route('admin.turnos.publicar', $turno) }}" data-confirm="¿Publicar el calendario para todos los voluntarios?">@csrf<button class="button" type="submit">Publicar calendario</button></form>
            </div>
        </div>
        <div class="mt-4 grid-two">
            @forelse ($disponibilidades as $respuesta)
                <p><strong>{{ $respuesta->volunteer->name }}</strong>: {{ collect($respuesta->weekdays)->map(fn ($day) => [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie'][$day] ?? '')->filter()->join(', ') ?: 'Sin días habituales' }}
                    @if ($respuesta->date_overrides)<br><span class="muted">Excepciones: {{ collect($respuesta->date_overrides)->map(fn ($value, $date) => \Illuminate\Support\Carbon::parse($date)->format('d/m').' '.($value === 'available' ? '(sí puede)' : '(no puede)'))->join(', ') }}</span>@endif
                </p>
            @empty<p class="muted">Todavía no han respondido disponibilidad.</p>@endforelse
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
        $uncoveredDaysCount = $turno->days->filter(fn ($day) => $day->is_open && $day->volunteers->count() < $turno->volunteers_per_day)->count();
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
        <div class="turno-sheet-stat"><span>Turnos asignados</span><strong>{{ $assignedShiftsCount }}</strong><small>personas · {{ $turno->volunteers_per_day }} por día como mínimo</small></div>
        <div class="turno-sheet-stat"><span>Actividades</span><strong>{{ $turno->activities->count() }}</strong><small>en el calendario mensual</small></div>
        <div @class(['turno-sheet-stat', 'is-alert' => $uncoveredDaysCount > 0])><span>Por completar</span><strong>{{ $uncoveredDaysCount }}</strong><small>{{ $uncoveredDaysCount ? 'días abiertos sin cobertura mínima' : 'días abiertos con cobertura completa' }}</small></div>
    </div>
    @if ($uncoveredDaysCount > 0 && auth()->user()->isAdmin())
        <p class="turno-sheet-alert" role="status">Hay {{ $uncoveredDaysCount }} {{ $uncoveredDaysCount === 1 ? 'fecha abierta' : 'fechas abiertas' }} con menos de {{ $turno->volunteers_per_day }} personas asignadas. Las verás marcadas en la planilla.</p>
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
                                $hasEnoughVolunteers = $dia && $dia->volunteers->count() >= $turno->volunteers_per_day;
                            @endphp
                            @if ($isInMonth && $dia)
                                <td @class(['turno-sheet-cell', 'is-open' => $dia->is_open, 'is-closed' => ! $dia->is_open, 'is-understaffed' => $dia->is_open && ! $hasEnoughVolunteers, 'is-weekend' => $calendarDate->isWeekend()])>
                                    <article class="turno-day-card">
                                        <div class="turno-day-heading">
                                            <span class="turno-day-number">{{ $calendarDate->format('j') }}</span>
                                            <span @class(['turno-day-state', 'is-open' => $dia->is_open, 'is-closed' => ! $dia->is_open])>{{ $dia->is_open ? 'Abierto' : 'Cerrado' }}</span>
                                        </div>
                                        @if ($dia->is_open)
                                            <div @class(['turno-coverage', 'is-covered' => $hasEnoughVolunteers, 'is-missing' => ! $hasEnoughVolunteers])>{{ $dia->volunteers->count() }}/{{ $turno->volunteers_per_day }} personas</div>
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
    <p class="turno-sheet-footnote">La cobertura mínima es una referencia para organizar los turnos. Las actividades y notas se muestran dentro de su fecha correspondiente.</p>
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
