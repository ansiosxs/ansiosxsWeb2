@extends('layouts.admin')
@section('title', 'Estanterías')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Ubicación física</p><h1>Estanterías y secciones</h1></div></div>
@if (auth()->user()->isAdmin())
    <form class="panel form-stack compact-form" method="post" action="{{ route('admin.estanterias.store') }}">@csrf<h2>Agregar estantería</h2><label>Nombre *<input name="nombre" required maxlength="255"></label><label>Descripción<textarea name="descripcion" rows="2"></textarea></label><button class="button">Guardar estantería</button></form>
@else
    <p class="muted">Vista de solo lectura. Contacta a un administrador para modificar las estanterías.</p>
@endif
<div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Descripción</th><th>Libros asignados</th>@if (auth()->user()->isAdmin())<th>Acciones</th>@endif</tr></thead><tbody>
@forelse ($muebles as $mueble)<tr><td>{{ $mueble->nombre }}</td><td>{{ $mueble->descripcion }}</td><td>{{ $mueble->libros_count }}</td>@if (auth()->user()->isAdmin())<td class="actions">
<details><summary>Editar</summary><form class="form-stack compact-form" method="post" action="{{ route('admin.estanterias.update', $mueble) }}">@csrf @method('put')<label>Nombre<input name="nombre" value="{{ $mueble->nombre }}" required></label><label>Descripción<textarea name="descripcion">{{ $mueble->descripcion }}</textarea></label><button class="button button-secondary">Guardar cambios</button></form></details>
<form method="post" action="{{ route('admin.estanterias.destroy', $mueble) }}" data-confirm="¿Eliminar esta estantería?">@csrf @method('delete')<button class="link-button">Eliminar</button></form></td>@endif</tr>
@empty<tr><td colspan="{{ auth()->user()->isAdmin() ? 4 : 3 }}">No hay estanterías registradas.</td></tr>@endforelse
</tbody></table></div>{{ $muebles->links() }}
@endsection
