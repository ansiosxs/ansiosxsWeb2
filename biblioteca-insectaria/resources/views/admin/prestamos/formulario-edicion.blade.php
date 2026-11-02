@extends('layouts.admin')
@section('title', 'Editar préstamo')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Préstamos</p><h1>Editar préstamo</h1></div></div>
<section class="panel">
    <p><strong>Libro:</strong> {{ $prestamo->libro->titulo }}</p>
    <p><strong>Socio:</strong> {{ $prestamo->socio->nombres }} {{ $prestamo->socio->apellidos }}</p>
    <form class="form-stack" method="post" action="{{ route('admin.prestamos.update', $prestamo) }}">@csrf @method('put')
        @if ($volver === 'prestamos')
            <input type="hidden" name="volver" value="prestamos">
            <input type="hidden" name="q" value="{{ $q }}">
        @endif
        <div class="grid-two">
            <label>Cantidad prestada<input type="number" name="cantidad" min="1" value="{{ $prestamo->cantidad }}" required></label>
            <label>Cantidad devuelta<input type="number" name="cantidad_devuelta" min="0" max="{{ $prestamo->cantidad }}" value="{{ $prestamo->cantidad_devuelta }}" required></label>
        </div>
        <div class="grid-two">
            <label>Fecha de préstamo<input type="date" name="fecha_prestamo" value="{{ $prestamo->fecha_prestamo->format('Y-m-d') }}" required></label>
            <label>Fecha de vencimiento<input type="date" name="fecha_vencimiento" value="{{ $prestamo->fecha_vencimiento->format('Y-m-d') }}" required></label>
        </div>
        <div class="flex gap-2">
            <button class="button" type="submit">Guardar cambios</button>
            <a class="button button-secondary" href="{{ $volver === 'prestamos' ? route('admin.prestamos.index', array_filter(['q' => $q])) : route('admin.socios.show', $prestamo->socio_id) }}">Cancelar</a>
        </div>
    </form>
</section>
@endsection