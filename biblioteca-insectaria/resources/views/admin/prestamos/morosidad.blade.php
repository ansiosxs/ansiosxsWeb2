@extends('layouts.admin')
@section('title', 'Morosidad')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Circulación</p><h1>Préstamos vencidos</h1></div><a href="{{ route('admin.prestamos.index') }}">Volver a préstamos</a></div>
<div class="table-wrap"><table><thead><tr><th>Socio</th><th>Teléfono</th><th>Libro</th><th>Unidades pendientes</th><th>Vencimiento</th><th>Registrado por</th><th>Días de atraso</th></tr></thead><tbody>
@forelse ($prestamos as $prestamo)<tr><td>{{ $prestamo->socio->nombres }} {{ $prestamo->socio->apellidos }}</td><td>{{ $prestamo->socio->telefono }}</td><td>{{ $prestamo->libro->titulo }}</td><td>{{ $prestamo->cantidad_pendiente }}</td><td>{{ $prestamo->fecha_vencimiento->format('d/m/Y') }}</td><td>{{ $prestamo->registradoPor?->name ?? 'Cuenta eliminada' }}</td><td>{{ $prestamo->fecha_vencimiento->diffInDays(today()) }}</td></tr>
@empty<tr><td colspan="7">No hay préstamos vencidos.</td></tr>@endforelse
</tbody></table></div>{{ $prestamos->links() }}
@endsection
