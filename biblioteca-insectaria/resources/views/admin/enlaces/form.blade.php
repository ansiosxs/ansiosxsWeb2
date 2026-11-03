@extends('layouts.admin')
@section('title', $enlace->exists ? 'Editar enlace' : 'Agregar enlace')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Sitio público</p><h1>{{ $enlace->exists ? 'Editar enlace' : 'Agregar enlace' }}</h1></div>
    <a href="{{ route('admin.enlaces.index') }}">Volver a enlaces</a>
</div>
<form class="panel form-stack" method="post" action="{{ $enlace->exists ? route('admin.enlaces.update', $enlace) : route('admin.enlaces.store') }}">
    @csrf @if ($enlace->exists) @method('put') @endif
    <label>Título del botón *
        <input name="titulo" value="{{ old('titulo', $enlace->titulo) }}" maxlength="120" required>
        @error('titulo')<span class="field-error">{{ $message }}</span>@enderror
    </label>
    <label>Descripción
        <input name="descripcion" value="{{ old('descripcion', $enlace->descripcion) }}" maxlength="300">
        @error('descripcion')<span class="field-error">{{ $message }}</span>@enderror
    </label>
    <label>Sección
        <input name="seccion" value="{{ old('seccion', $enlace->seccion) }}" maxlength="120" placeholder="Por ejemplo: Redes Sociales">
        <span class="muted">Déjalo vacío para mostrar el enlace sin encabezado de sección.</span>
        @error('seccion')<span class="field-error">{{ $message }}</span>@enderror
    </label>
    <label>URL
        <input name="url" type="url" inputmode="url" value="{{ old('url', $enlace->url) }}" maxlength="2048" placeholder="Agrega la URL cuando esté disponible">
        <span class="muted">Puedes guardarlo sin URL. El enlace se mostrará como pendiente y no será clickeable hasta que agregues una dirección HTTP o HTTPS.</span>
        @error('url')<span class="field-error">{{ $message }}</span>@enderror
    </label>
    <label>Orden de aparición *
        <input name="orden" type="number" min="0" max="9999" value="{{ old('orden', $enlace->orden ?? 0) }}" required>
        <span class="muted">Los números más bajos aparecen primero. Si dos enlaces comparten orden, se muestran en el orden en que se crearon.</span>
        @error('orden')<span class="field-error">{{ $message }}</span>@enderror
    </label>
    <input name="activo" type="hidden" value="0">
    <label class="flex items-center gap-2">
        <input name="activo" type="checkbox" value="1" @checked(old('activo', $enlace->activo ?? true))>
        Mostrar este enlace en la página pública
    </label>
    <button class="button" type="submit">Guardar enlace</button>
</form>
@endsection
