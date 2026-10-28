@extends('layouts.admin')
@section('title', 'Socios')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Membresías</p><h1>Socios</h1></div><div class="inline-form">@if (auth()->user()?->isAdmin())<a class="button button-secondary" href="{{ route('admin.exportar.socios') }}">Exportar CSV</a>@endif<a class="button" href="{{ route('admin.socios.create') }}">Inscribir socio</a></div></div>
<form class="search-form" method="get"><label for="q">Buscar por nombre o RUN</label><div class="inline-form"><input id="q" name="q" value="{{ $q }}" type="search"><button class="button">Buscar</button></div></form>
<div class="table-wrap"><table><thead><tr><th>Socio</th><th>RUN</th><th>Comuna</th><th>Vigente hasta</th><th>Registrado por</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse ($socios as $socio)<tr><td>{{ $socio->nombres }} {{ $socio->apellidos }}</td><td>{{ $socio->run }}</td><td>{{ $socio->comuna }}</td><td>{{ $socio->vigente_hasta->format('d/m/Y') }}</td><td>{{ $socio->registradoPor?->name ?? 'Registro histórico' }}</td><td>{{ $socio->activo ? ($socio->vigente_hasta->lt(today()) ? 'Vencida' : 'Vigente') : 'Inactivo' }}</td><td><a href="{{ route('admin.socios.show', $socio) }}">Ficha</a></td></tr>
@empty<tr><td colspan="7">No hay socios para mostrar.</td></tr>@endforelse
</tbody></table></div>{{ $socios->links() }}
@endsection
