@extends('layouts.admin')
@section('title', 'Ficha de socio')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Ficha de socio</p><h1>{{ $socio->nombres }} {{ $socio->apellidos }}</h1></div><a class="button button-secondary" href="{{ route('admin.socios.edit', $socio) }}">Editar datos</a></div>
<section class="panel">
    <p><strong>RUN:</strong> {{ $socio->run }}</p><p><strong>Teléfono:</strong> {{ $socio->telefono }}</p><p><strong>Correo:</strong> {{ $socio->email ?? 'No informado' }}</p>
    <p><strong>Comuna:</strong> {{ $socio->comuna }} · <strong>Ocupación:</strong> {{ $socio->ocupacion ?? 'No informada' }}</p>
    <p><strong>Membresía hasta:</strong> {{ $socio->vigente_hasta->format('d/m/Y') }} · <strong>Estado:</strong> {{ $socio->activo ? ($socio->vigente_hasta->lt(today()) ? 'Vencida' : 'Vigente') : 'Inactivo' }}</p>
    <div class="flex flex-wrap items-end justify-end gap-3">
        <form method="post" action="{{ route('admin.socios.caducar', $socio) }}" data-confirm="¿Confirmas que quieres caducar la membresía de este socio?">@csrf<button class="button button-danger" type="submit">Caducar</button></form>
        <form class="flex flex-wrap items-end gap-2" method="post" action="{{ route('admin.socios.caducidad', $socio) }}" data-confirm="¿Confirmas el cambio de fecha de caducidad?">@csrf @method('put')
            <label class="text-sm font-bold">Cambiar fecha de caducación<input type="date" name="vigente_hasta" value="{{ $socio->vigente_hasta->format('Y-m-d') }}" required></label>
            <button class="button button-secondary" type="submit">Guardar fecha</button>
        </form>
        <form method="post" action="{{ route('admin.socios.renovar', $socio) }}">@csrf<button class="button" type="submit">Renovar membresía por un año</button></form>
    </div>
</section>
<section class="panel"><h2>Historial de préstamos</h2><div class="table-wrap"><table><thead><tr><th>Libro</th><th>Prestadas</th><th>Devueltas</th><th>Préstamo</th><th>Vencimiento</th><th>Devolución</th><th>Registrado por</th><th>Estado</th></tr></thead><tbody>
@forelse ($socio->prestamos as $prestamo)<tr><td>{{ $prestamo->libro->titulo }}</td><td>{{ $prestamo->cantidad }}</td><td>{{ $prestamo->cantidad_devuelta }}</td><td>{{ $prestamo->fecha_prestamo->format('d/m/Y') }}</td><td>{{ $prestamo->fecha_vencimiento->format('d/m/Y') }}</td><td>{{ $prestamo->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td><td>{{ $prestamo->registradoPor?->name ?? 'Cuenta eliminada' }}</td><td>{{ ucfirst($prestamo->estado) }}</td></tr>
@empty<tr><td colspan="6">Sin préstamos registrados.</td></tr>@endforelse
</tbody></table></div></section>
@endsection
