@extends('layouts.admin')
@section('title', $anuncio->exists ? 'Editar anuncio' : 'Crear anuncio')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Portal público</p><h1>{{ $anuncio->exists ? 'Editar anuncio' : 'Crear anuncio' }}</h1></div><a href="{{ route('admin.anuncios.index') }}">Volver</a></div>
<form class="panel form-stack" method="post" action="{{ $anuncio->exists ? route('admin.anuncios.update', $anuncio) : route('admin.anuncios.store') }}">
    @csrf @if ($anuncio->exists) @method('put') @endif
    <label>Título *<input name="titulo" value="{{ old('titulo', $anuncio->titulo) }}" maxlength="255" required>@error('titulo')<span class="field-error">{{ $message }}</span>@enderror</label>
    <label>Texto *<textarea name="cuerpo" rows="6" required>{{ old('cuerpo', $anuncio->cuerpo) }}</textarea>@error('cuerpo')<span class="field-error">{{ $message }}</span>@enderror</label>
    <div class="grid-two"><label>Publicar desde *<input name="publicar_desde" type="date" value="{{ old('publicar_desde', $anuncio->publicar_desde?->format('Y-m-d')) }}" required>@error('publicar_desde')<span class="field-error">{{ $message }}</span>@enderror</label><label>Publicar hasta<input name="publicar_hasta" type="date" value="{{ old('publicar_hasta', $anuncio->publicar_hasta?->format('Y-m-d')) }}">@error('publicar_hasta')<span class="field-error">{{ $message }}</span>@enderror</label></div>
    <button class="button" type="submit">Guardar anuncio</button>
</form>
@endsection
