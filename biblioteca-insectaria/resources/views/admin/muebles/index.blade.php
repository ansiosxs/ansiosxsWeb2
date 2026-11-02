@extends('layouts.admin')
@section('title', 'Muebles')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Ubicación física</p><h1>Muebles y secciones</h1></div></div>
<form class="panel form-stack compact-form" method="post" action="{{ route('admin.muebles.store') }}">@csrf<h2>Agregar mueble</h2><label>Nombre *<input name="nombre" required maxlength="255"></label><label>Descripción<textarea name="descripcion" rows="2"></textarea></label><button class="button">Guardar mueble</button></form>
<div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Descripción</th><th>Libros asignados</th><th>Acciones</th></tr></thead><tbody>
@forelse ($muebles as $mueble)<tr><td>{{ $mueble->nombre }}</td><td>{{ $mueble->descripcion }}</td><td>{{ $mueble->libros_count }}</td><td class="actions">
<details><summary>Editar</summary><form class="form-stack compact-form" method="post" action="{{ route('admin.muebles.update', $mueble) }}">@csrf @method('put')<label>Nombre<input name="nombre" value="{{ $mueble->nombre }}" required></label><label>Descripción<textarea name="descripcion">{{ $mueble->descripcion }}</textarea></label><button class="button button-secondary">Guardar cambios</button></form></details>
<form method="post" action="{{ route('admin.muebles.destroy', $mueble) }}">@csrf @method('delete')<button class="link-button">Eliminar</button></form></td></tr>
@empty<tr><td colspan="4">No hay muebles registrados.</td></tr>@endforelse
</tbody></table></div>{{ $muebles->links() }}
@endsection
