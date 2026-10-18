@extends('layouts.admin')
@section('title', 'Libros')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Catálogo</p><h1>Libros</h1></div><a class="button" href="{{ route('admin.libros.create') }}">Agregar libro</a></div>
<form class="search-form" method="get"><label for="q">Buscar por título o autor</label><div class="inline-form"><input id="q" name="q" value="{{ $q }}" type="search"><button class="button">Buscar</button></div></form>
<div class="table-wrap"><table><thead><tr><th>Libro</th><th>Ubicación</th><th>En inventario</th><th>Disponibles</th><th></th></tr></thead><tbody>
@forelse ($libros as $libro)
<tr><td><strong>{{ $libro->titulo }}</strong><br><span class="muted">{{ $libro->autor }}</span></td><td>{{ $libro->mueble?->nombre ?? 'Sin asignar' }}</td><td>{{ $libro->cantidad_total }}</td><td>{{ $libro->cantidad_disponible }}</td><td class="actions"><a href="{{ route('admin.libros.show', $libro) }}">Ficha</a><a href="{{ route('admin.libros.edit', $libro) }}">Editar</a></td></tr>
@empty<tr><td colspan="5">No hay libros para mostrar.</td></tr>@endforelse
</tbody></table></div>
{{ $libros->links() }}
@endsection
