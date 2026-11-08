@extends('layouts.admin')
@section('title', 'Préstamos')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Circulación</p><h1>Préstamos y devoluciones</h1></div><div class="inline-form">@if (auth()->user()?->isAdmin())<a class="button button-secondary" href="{{ route('admin.exportar.prestamos') }}">Exportar CSV</a>@endif<a href="{{ route('admin.morosidad') }}">Ver morosidad</a></div></div>
<section class="panel"><h2>Registrar préstamo</h2><p>Busca un título, indica cuántas unidades se lleva el socio y confirma.</p>
    <form class="form-stack" method="post" action="{{ route('admin.prestamos.store') }}" data-resiliente="prestamo" data-libros-url="{{ route('admin.prestamos.libros') }}">
        @csrf
        <label>Buscar por título, autor o ISBN
            <span class="loan-book-search">
                <input type="search" autocomplete="off" inputmode="search" data-libro-busqueda placeholder="Escribe el título, autor o ISBN" required>
                <button class="button button-secondary loan-scan-button" type="button" data-libro-escanear>Escanear ISBN</button>
            </span>
        </label>
        <section class="isbn-scanner" data-isbn-scanner hidden aria-label="Escáner de ISBN">
            <video data-isbn-video playsinline muted aria-label="Vista de la cámara para escanear el ISBN"></video>
            <div class="isbn-scanner-controls">
                <p class="muted" data-isbn-estado aria-live="polite">Apunta la cámara al código de barras del libro.</p>
                <button class="button button-secondary" type="button" data-isbn-cancelar>Cancelar escaneo</button>
            </div>
        </section>
        <div class="form-stack" data-libro-resultados hidden role="listbox" aria-label="Libros disponibles"></div>
        <p class="muted" data-libro-estado aria-live="polite"></p>
        <input type="hidden" name="libro_id" value="{{ old('libro_id') }}" data-libro-id>
        @error('libro_id')<span class="field-error">{{ $message }}</span>@enderror
        <div class="grid-two">
            <label>Título<input readonly data-libro-campo="titulo"></label>
            <label>Autor<input readonly data-libro-campo="autor"></label>
            <label>Editorial<input readonly data-libro-campo="editorial"></label>
            <label>ISBN<input readonly data-libro-campo="isbn"></label>
            <label>Unidades disponibles<input readonly data-libro-campo="cantidad_disponible"></label>
        </div>
        <div class="grid-two">
            <label>Cantidad a prestar<input name="cantidad" type="number" min="1" max="1000000" value="{{ old('cantidad', 1) }}" required>@error('cantidad')<span class="field-error">{{ $message }}</span>@enderror</label>
            <label>Buscar socio por nombre o RUT
                <input type="search" autocomplete="off" inputmode="search" data-socio-busqueda placeholder="Escribe nombre, apellido o RUT" aria-label="Buscar socio por nombre, apellido o RUT">
            </label>
            <label>Socio
                <select name="socio_id" required data-socios-select>
                    <option value="">Selecciona un socio</option>
                    @foreach ($socios as $socio)
                        <option value="{{ $socio->id }}" data-socio-nombre="{{ $socio->nombres }} {{ $socio->apellidos }}" data-socio-run="{{ $socio->run }}" @selected(old('socio_id') == $socio->id)>{{ $socio->apellidos }}, {{ $socio->nombres }} — {{ $socio->run }}</option>
                    @endforeach
                </select>
                <span class="muted" data-socio-estado aria-live="polite">Escribe para filtrar la lista de socios.</span>
                @error('socio_id')<span class="field-error">{{ $message }}</span>@enderror
            </label>
        </div>
        <input type="hidden" name="operacion_uuid" value="{{ $operacionUuid }}">
        <button class="button" type="submit">Confirmar préstamo</button>
        <div class="resilience-notice" hidden role="alert"></div>
    </form>
</section>
<section class="panel"><h2>Préstamos activos</h2><p>Registra la devolución completa o parcial en la fila del préstamo.</p>
<form class="inline-form" method="get" action="{{ route('admin.prestamos.index') }}" data-busqueda-viva>
    <label for="buscar-prestamos">Buscar por título o nombre del socio</label>
    <input id="buscar-prestamos" type="search" name="q" value="{{ $q }}" placeholder="Título, nombre o apellido">
    <button class="button button-secondary" type="submit">Buscar</button>
    @if ($q !== '')<a href="{{ route('admin.prestamos.index') }}">Limpiar búsqueda</a>@endif
    <span class="muted" data-busqueda-estado aria-live="polite"></span>
</form>
<div class="table-wrap"><table><thead><tr><th>Libro</th><th>Socio</th><th>Unidades pendientes</th><th>Vencimiento</th><th>Registrado por</th><th>Devolver</th><th>Acciones</th></tr></thead>
@include('admin.prestamos._filas', ['prestamos' => $prestamos, 'q' => $q])
</table></div>
<div class="mt-4" data-paginacion-prestamos>@include('admin.prestamos._paginacion', ['prestamos' => $prestamos])</div>
</section>
<section class="panel"><h2>Contactar por teléfono</h2><p>Préstamos que vencen pronto y cuyos socios no tienen correo registrado.</p>
@forelse ($contactar as $prestamo)<p>{{ $prestamo->socio->nombres }} {{ $prestamo->socio->apellidos }} · {{ $prestamo->socio->telefono }} · {{ $prestamo->libro->titulo }} ({{ $prestamo->cantidad_pendiente }} unidades)</p>@empty<p>No hay contactos pendientes.</p>@endforelse
</section>
@endsection
