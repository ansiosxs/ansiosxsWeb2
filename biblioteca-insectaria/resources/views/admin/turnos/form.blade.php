@extends('layouts.admin')
@section('title', 'Crear calendario')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Organización de voluntariado</p><h2>Crear calendario mensual</h2></div><a href="{{ route('admin.turnos.index') }}">Volver</a></div>
<form class="panel form-stack" method="post" action="{{ route('admin.turnos.store') }}">
    @csrf
    <p>Se preparará un borrador con todos los días del mes. Los días de lunes a viernes comenzarán abiertos y los fines de semana cerrados; puedes ajustar las fechas antes de publicar.</p>
    <div class="grid-two">
        <label>Año *<input name="year" type="number" min="2020" max="2100" value="{{ old('year', now()->year) }}" required></label>
        <label>Mes *<select name="month" required>
            @foreach (range(1, 12) as $month)
                <option value="{{ $month }}" @selected(old('month', now()->month) == $month)>{{ ucfirst(\Illuminate\Support\Carbon::create(now()->year, $month, 1)->translatedFormat('F')) }}</option>
            @endforeach
        </select></label>
    </div>
    <p class="muted">El borrador también se crea automáticamente el último día de cada mes para el mes siguiente. La participación y los días preferidos se copian del calendario anterior y puedes cambiarlos antes de generar la propuesta.</p>
    <button class="button" type="submit">Crear o abrir borrador</button>
</form>
@endsection
