@extends('layouts.admin')
@section('title', 'Morosidad')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Circulación</p><h1>Préstamos vencidos</h1></div><a href="{{ route('admin.prestamos.index') }}">Volver a préstamos</a></div>
<div class="table-wrap"><table><thead><tr><th>Socio</th><th>Teléfono</th><th>Libro</th><th>Unidades pendientes</th><th>Vencimiento</th><th>Registrado por</th><th>Días de atraso</th>@if (auth()->user()->isAdmin())<th>Acciones</th>@endif</tr></thead><tbody>
@forelse ($prestamos as $prestamo)<tr><td>{{ $prestamo->socio->nombres }} {{ $prestamo->socio->apellidos }}</td><td>{{ $prestamo->socio->telefono }}</td><td>{{ $prestamo->libro->titulo }}</td><td>{{ $prestamo->cantidad_pendiente }}</td><td>{{ $prestamo->fecha_vencimiento->format('d/m/Y') }}</td><td>{{ $prestamo->registradoPor?->name ?? 'Cuenta eliminada' }}</td><td>{{ $prestamo->fecha_vencimiento->diffInDays(today()) }}</td>@if (auth()->user()->isAdmin())<td><div class="flex flex-wrap gap-2">
    <form method="post" action="{{ route('admin.prestamos.correo-morosidad', $prestamo) }}">@csrf<button class="button button-secondary" type="submit">Enviar correo</button></form>
    <form method="post" action="{{ route('admin.prestamos.marcar-devuelto', $prestamo) }}" onsubmit="return confirm('¿Confirmas que este préstamo ya fue devuelto?')">@csrf<button class="button" type="submit">Marcar como devuelto</button></form>
</div></td>@endif</tr>
@empty<tr><td colspan="{{ auth()->user()->isAdmin() ? 8 : 7 }}">No hay préstamos vencidos.</td></tr>@endforelse
</tbody></table></div>{{ $prestamos->links() }}
@endsection
