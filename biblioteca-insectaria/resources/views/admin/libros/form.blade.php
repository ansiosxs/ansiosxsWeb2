@extends('layouts.admin')
@section('title', $libro->exists ? 'Editar libro' : 'Nuevo libro')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Catálogo</p><h1>{{ $libro->exists ? 'Editar libro' : 'Agregar libro' }}</h1></div><a href="{{ route('admin.libros.index') }}">Volver a libros</a></div>
<form class="panel form-stack" method="post" enctype="multipart/form-data" action="{{ $libro->exists ? route('admin.libros.update', $libro) : route('admin.libros.store') }}" data-isbn-form data-book-image-form>
    @csrf @if ($libro->exists) @method('put') @endif
    <label>Título *<input name="titulo" value="{{ old('titulo', $libro->titulo) }}" required maxlength="255">@error('titulo')<span class="field-error">{{ $message }}</span>@enderror</label>
    <label>Autor *<input name="autor" value="{{ old('autor', $libro->autor) }}" required maxlength="255">@error('autor')<span class="field-error">{{ $message }}</span>@enderror</label>
    <div class="grid-two">
        <label>ISBN
            <span class="loan-book-search">
                <input name="isbn" data-isbn-input inputmode="numeric" value="{{ old('isbn', $libro->isbn) }}" maxlength="20">
                <button class="button button-secondary loan-scan-button" type="button" data-isbn-escanear>Escanear ISBN</button>
            </span>
        </label>
        <label>Editorial<input name="editorial" value="{{ old('editorial', $libro->editorial) }}" maxlength="255"></label>
    </div>
    <section class="isbn-scanner" data-isbn-scanner hidden aria-label="Escáner de ISBN">
        <video data-isbn-video playsinline muted aria-label="Vista de la cámara para escanear el ISBN"></video>
        <div class="isbn-scanner-controls">
            <p class="muted" data-isbn-estado aria-live="polite">Apunta la cámara al código de barras del libro.</p>
            <button class="button button-secondary" type="button" data-isbn-cancelar>Cancelar escaneo</button>
        </div>
    </section>
    <div class="grid-two"><label>Año<input name="anio" type="number" min="1000" max="9999" value="{{ old('anio', $libro->anio) }}"></label>
        <fieldset class="form-stack">
            <legend>Imagen</legend>
            <label>URL de imagen<input name="portada_url" type="url" value="{{ old('portada_url', $libro->portada_url) }}" data-book-image-url></label>
            <label>Subir desde tu dispositivo<input name="portada" type="file" accept="image/jpeg,image/png,image/webp" data-book-image-file>@error('portada')<span class="field-error">{{ $message }}</span>@enderror</label>
            <img class="h-40 w-auto rounded-lg border object-contain" src="{{ old('portada_url', $libro->portada_url) }}" alt="Vista previa de la imagen del libro" data-book-image-preview @if (!old('portada_url', $libro->portada_url)) hidden @endif>
            <span class="muted">Puedes ingresar una URL o subir una imagen (JPG, PNG o WebP; máximo 5 MB). Si eliges ambas opciones, se usará el archivo.</span>
            @error('portada_url')<span class="field-error">{{ $message }}</span>@enderror
        </fieldset>
    </div>
    <label>Unidades en inventario *<input name="cantidad_total" type="number" min="0" max="1000000" value="{{ old('cantidad_total', $libro->cantidad_total ?? 0) }}" required><span class="muted">Incluye las unidades actualmente prestadas; el sistema calcula las disponibles.</span>@error('cantidad_total')<span class="field-error">{{ $message }}</span>@enderror</label>
    <label>Estantería o sección<select name="mueble_id"><option value="">Sin asignar</option>@foreach ($muebles as $mueble)<option value="{{ $mueble->id }}" @selected(old('mueble_id', $libro->mueble_id) == $mueble->id)>{{ $mueble->nombre }}</option>@endforeach</select></label>
    <button class="button" type="submit">Guardar libro</button>
</form>
@endsection
