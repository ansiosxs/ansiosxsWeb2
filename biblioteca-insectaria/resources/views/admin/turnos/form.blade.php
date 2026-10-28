@extends('layouts.admin')
@section('title', 'Crear calendario')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Organización de voluntariado</p><h2>Crear calendario mensual</h2></div><a href="{{ route('admin.turnos.index') }}">Volver</a></div>
<form class="panel form-stack" method="post" action="{{ route('admin.turnos.store') }}">
    @csrf
    <p>Se preparará una planilla con todos los días del mes. Lunes a viernes comenzarán abiertos y los fines de semana cerrados; puedes cambiar cada día y abrir fechas especiales.</p>
    <div class="grid-two">
        <label>Año *<input name="year" type="number" min="2020" max="2100" value="{{ old('year', now()->year) }}" required></label>
        <label>Mes *<select name="month" required>
            @foreach (range(1, 12) as $month)
                <option value="{{ $month }}" @selected(old('month', now()->month) == $month)>{{ ucfirst(\Illuminate\Support\Carbon::create(now()->year, $month, 1)->translatedFormat('F')) }}</option>
            @endforeach
        </select></label>
    </div>
    <div class="grid-two">
        <label>Cobertura mínima por día *<input name="volunteers_per_day" type="number" min="1" max="255" value="{{ old('volunteers_per_day', 2) }}" required></label>
        <label>Cierre para responder disponibilidad (opcional)<input name="availability_closes_at" type="datetime-local" value="{{ old('availability_closes_at') }}"></label>
    </div>
    <p class="muted">La cobertura es orientativa: puedes asignar más personas a fechas con actividades y menos restricciones que en el horario habitual. La propuesta intenta repartir turnos de forma equilibrada y siempre puedes editarla antes de publicar.</p>
    <button class="button" type="submit">Crear borrador</button>
</form>
@endsection
