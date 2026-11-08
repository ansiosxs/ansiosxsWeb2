@extends('layouts.admin')
@section('title', 'Configuración')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Administración</p><h1>Configuración general</h1></div>
</div>
<p class="muted">Estas preferencias ajustan el funcionamiento del panel. Ocultar una sección solo la quita del menú; no cambia los permisos de acceso existentes.</p>

<form class="form-stack" method="post" action="{{ route('admin.configuracion.update') }}">
    @csrf @method('put')
    <section class="panel form-stack">
        <div>
            <h2>Préstamos</h2>
            <p class="muted">Define los valores que se aplican al registrar nuevos préstamos.</p>
        </div>
        <div class="grid-two">
            <label>Duración predeterminada (días)
                <input type="number" name="plazo_prestamo_dias" min="1" max="365" required value="{{ old('plazo_prestamo_dias', $configuracion->plazo_prestamo_dias) }}">
                @error('plazo_prestamo_dias')<span class="field-error">{{ $message }}</span>@enderror
            </label>
            <label>Máximo de unidades activas por socio
                <input type="number" name="max_prestamos_activos" min="1" max="100" required value="{{ old('max_prestamos_activos', $configuracion->max_prestamos_activos) }}">
                @error('max_prestamos_activos')<span class="field-error">{{ $message }}</span>@enderror
            </label>
            <label>Días de anticipación del recordatorio
                <input type="number" name="dias_recordatorio" min="0" max="365" required value="{{ old('dias_recordatorio', $configuracion->dias_recordatorio) }}">
                <span class="muted">Usa 0 para no crear avisos previos al vencimiento.</span>
                @error('dias_recordatorio')<span class="field-error">{{ $message }}</span>@enderror
            </label>
        </div>
        <label class="recordatorio-toggle">
            <input type="hidden" name="permitir_membresia_vencida" value="0">
            <input type="checkbox" name="permitir_membresia_vencida" value="1" @checked(old('permitir_membresia_vencida', $configuracion->permitir_membresia_vencida))>
            <span><strong>Permitir préstamos con membresía vencida</strong><small>La excepción no permite retirar libros si el socio tiene préstamos vencidos.</small></span>
        </label>
    </section>

    <section class="panel form-stack">
        <div>
            <h2>Secciones visibles del menú</h2>
            <p class="muted">Configura qué enlaces aparecen para cada tipo de usuario. Las autorizaciones actuales de cada rol no cambian.</p>
        </div>
        @foreach ($roles as $rol => $nombreRol)
            <fieldset class="form-stack border-t border-[#e7e0d8] pt-4">
                <legend class="font-bold">{{ $nombreRol }}</legend>
                <div class="grid-two">
                    @foreach (\App\Models\ConfiguracionGeneral::SECCIONES_POR_ROL[$rol] as $seccion)
                        <label class="recordatorio-toggle">
                            <input type="checkbox" name="secciones_visibles[{{ $rol }}][]" value="{{ $seccion }}" @checked(in_array($seccion, old("secciones_visibles.{$rol}", $configuracion->seccionesVisiblesPara($rol)), true))>
                            <span>{{ \App\Models\ConfiguracionGeneral::SECCIONES[$seccion] }}</span>
                        </label>
                    @endforeach
                </div>
                @error("secciones_visibles.{$rol}")<span class="field-error">{{ $message }}</span>@enderror
            </fieldset>
        @endforeach
    </section>

    <section class="panel form-stack">
        <div>
            <h2>Estadísticas del dashboard</h2>
            <p class="muted">Selecciona qué indicadores y gráficos se muestran en el resumen del panel.</p>
        </div>
        <div class="grid-two">
            @foreach (\App\Models\ConfiguracionGeneral::ESTADISTICAS as $clave => $nombre)
                <label class="recordatorio-toggle">
                    <input type="checkbox" name="estadisticas_dashboard[]" value="{{ $clave }}" @checked(in_array($clave, old('estadisticas_dashboard', $configuracion->estadisticas_dashboard ?? []), true))>
                    <span>{{ $nombre }}</span>
                </label>
            @endforeach
        </div>
        @error('estadisticas_dashboard')<span class="field-error">{{ $message }}</span>@enderror
        @error('estadisticas_dashboard.*')<span class="field-error">{{ $message }}</span>@enderror
    </section>

    <button class="button" type="submit">Guardar configuración</button>
</form>
@endsection
