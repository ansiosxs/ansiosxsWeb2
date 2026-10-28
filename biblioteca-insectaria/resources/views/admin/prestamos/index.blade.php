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
            <label>Año<input readonly data-libro-campo="anio"></label>
            <label>Unidades disponibles<input readonly data-libro-campo="cantidad_disponible"></label>
        </div>
        <div class="grid-two">
            <label>Cantidad a prestar<input name="cantidad" type="number" min="1" max="1000000" value="{{ old('cantidad', 1) }}" required>@error('cantidad')<span class="field-error">{{ $message }}</span>@enderror</label>
            <label>Socio<select name="socio_id" required><option value="">Selecciona un socio</option>@foreach ($socios as $socio)<option value="{{ $socio->id }}" @selected(old('socio_id') == $socio->id)>{{ $socio->apellidos }}, {{ $socio->nombres }} — {{ $socio->run }}</option>@endforeach</select>@error('socio_id')<span class="field-error">{{ $message }}</span>@enderror</label>
        </div>
        <input type="hidden" name="operacion_uuid" value="{{ $operacionUuid }}">
        <button class="button" type="submit">Confirmar préstamo</button>
        <div class="resilience-notice" hidden role="alert"></div>
    </form>
</section>
<section class="panel"><h2>Préstamos activos</h2><p>Registra la devolución completa o parcial en la fila del préstamo.</p><div class="table-wrap"><table><thead><tr><th>Libro</th><th>Socio</th><th>Unidades pendientes</th><th>Vencimiento</th><th>Registrado por</th><th>Devolver</th></tr></thead><tbody>
@forelse ($prestamos as $prestamo)
<tr><td>{{ $prestamo->libro->titulo }}<br><span class="muted">{{ $prestamo->cantidad }} prestadas · {{ $prestamo->cantidad_devuelta }} devueltas</span></td><td>{{ $prestamo->socio->nombres }} {{ $prestamo->socio->apellidos }}<br><span class="muted">{{ $prestamo->socio->telefono }}</span></td><td>{{ $prestamo->cantidad_pendiente }}</td><td>{{ $prestamo->fecha_vencimiento->format('d/m/Y') }}{{ $prestamo->fecha_vencimiento->lt(today()) ? ' · Vencido' : '' }}</td><td>{{ $prestamo->registradoPor?->name ?? 'Cuenta eliminada' }}</td><td>
    <form class="inline-form" method="post" action="{{ route('admin.prestamos.devolver', $prestamo) }}">@csrf
        <label class="sr-only" for="devolucion-{{ $prestamo->id }}">Cantidad a devolver</label>
        <input id="devolucion-{{ $prestamo->id }}" name="cantidad" type="number" min="1" max="{{ $prestamo->cantidad_pendiente }}" value="{{ $prestamo->cantidad_pendiente }}" required>
        <button class="button button-secondary" type="submit">Registrar</button>
    </form>
    @error('cantidad')<span class="field-error">{{ $message }}</span>@enderror
</td></tr>
@empty<tr><td colspan="6">No hay préstamos activos.</td></tr>@endforelse
</tbody></table></div>{{ $prestamos->links() }}</section>
<section class="panel"><h2>Contactar por teléfono</h2><p>Préstamos que vencen pronto y cuyos socios no tienen correo registrado.</p>
@forelse ($contactar as $prestamo)<p>{{ $prestamo->socio->nombres }} {{ $prestamo->socio->apellidos }} · {{ $prestamo->socio->telefono }} · {{ $prestamo->libro->titulo }} ({{ $prestamo->cantidad_pendiente }} unidades)</p>@empty<p>No hay contactos pendientes.</p>@endforelse
</section>
@endsection
