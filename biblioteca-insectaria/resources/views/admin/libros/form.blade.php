@extends('layouts.admin')
@section('title', $libro->exists ? 'Editar libro' : 'Nuevo libro')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Catálogo</p><h1>{{ $libro->exists ? 'Editar libro' : 'Agregar libro' }}</h1></div><a href="{{ route('admin.libros.index') }}">Volver a libros</a></div>
<form class="panel form-stack" method="post" action="{{ $libro->exists ? route('admin.libros.update', $libro) : route('admin.libros.store') }}">
    @csrf @if ($libro->exists) @method('put') @endif
    <label>Título *<input name="titulo" value="{{ old('titulo', $libro->titulo) }}" required maxlength="255">@error('titulo')<span class="field-error">{{ $message }}</span>@enderror</label>
    <label>Autor *<input name="autor" value="{{ old('autor', $libro->autor) }}" required maxlength="255">@error('autor')<span class="field-error">{{ $message }}</span>@enderror</label>
    <div class="grid-two"><label>ISBN<input name="isbn" value="{{ old('isbn', $libro->isbn) }}" maxlength="20"></label><label>Editorial<input name="editorial" value="{{ old('editorial', $libro->editorial) }}" maxlength="255"></label></div>
    <div class="grid-two"><label>Año<input name="anio" type="number" min="1000" max="9999" value="{{ old('anio', $libro->anio) }}"></label><label>URL de portada<input name="portada_url" type="url" value="{{ old('portada_url', $libro->portada_url) }}"></label></div>
    <label>Unidades en inventario *<input name="cantidad_total" type="number" min="0" max="1000000" value="{{ old('cantidad_total', $libro->cantidad_total ?? 0) }}" required><span class="muted">Incluye las unidades actualmente prestadas; el sistema calcula las disponibles.</span>@error('cantidad_total')<span class="field-error">{{ $message }}</span>@enderror</label>
    <label>Estantería o sección<select name="mueble_id"><option value="">Sin asignar</option>@foreach ($muebles as $mueble)<option value="{{ $mueble->id }}" @selected(old('mueble_id', $libro->mueble_id) == $mueble->id)>{{ $mueble->nombre }}</option>@endforeach</select></label>
    <button class="button" type="submit">Guardar libro</button>
</form>
@endsection
