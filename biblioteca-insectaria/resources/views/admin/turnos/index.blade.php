@extends('layouts.admin')
@section('title', 'Turnos y actividades')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Organización de voluntariado</p><h2>Turnos y actividades</h2></div>
    @if (auth()->user()->isAdmin())
        <a class="button" href="{{ route('admin.turnos.create') }}">Crear calendario mensual</a>
    @endif
</div>
<section class="panel">
    <h3>Calendarios mensuales</h3>
    <div class="table-wrap"><table>
        <thead><tr><th>Mes</th><th>Estado</th><th>Días de apertura</th><th>Disponibilidades</th><th></th></tr></thead>
        <tbody>
        @forelse ($turnos as $turno)
            <tr>
                <td><strong>{{ ucfirst($turno->month_label) }}</strong></td>
                <td>{{ $turno->status === 'published' ? 'Publicado' : 'Borrador' }}</td>
                <td>{{ $turno->days_count }}</td>
                <td>{{ $turno->availabilities_count }}</td>
                <td><a class="button button-secondary" href="{{ route('admin.turnos.show', $turno) }}">Abrir calendario</a></td>
            </tr>
        @empty
            <tr><td colspan="5">Todavía no hay calendarios. El administrador puede crear el primer mes.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $turnos->links() }}
</section>
@endsection
