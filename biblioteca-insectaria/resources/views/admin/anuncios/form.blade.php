@extends('layouts.admin')
@section('title', $anuncio->exists ? 'Editar anuncio' : 'Crear anuncio')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Portal público</p><h1>{{ $anuncio->exists ? 'Editar anuncio' : 'Crear anuncio' }}</h1></div><a href="{{ route('admin.anuncios.index') }}">Volver</a></div>
<form class="panel form-stack" method="post" action="{{ $anuncio->exists ? route('admin.anuncios.update', $anuncio) : route('admin.anuncios.store') }}">
    @csrf @if ($anuncio->exists) @method('put') @endif
    <label>Título *<input name="titulo" value="{{ old('titulo', $anuncio->titulo) }}" maxlength="255" required>@error('titulo')<span class="field-error">{{ $message }}</span>@enderror</label>
    <div>
        <label id="anuncio-cuerpo-label" for="anuncio-cuerpo-editor">Texto *</label>
        <div class="rich-text-editor" data-rich-text-editor>
            <div class="rich-text-toolbar" role="toolbar" aria-label="Formato del texto">
                <div class="rich-text-toolbar-group" aria-label="Estilo de texto">
                    <button type="button" data-editor-command="bold" aria-label="Negrita" aria-pressed="false" title="Negrita (Ctrl+B)"><strong>B</strong><span>Negrita</span></button>
                    <button type="button" data-editor-command="italic" aria-label="Cursiva" aria-pressed="false" title="Cursiva (Ctrl+I)"><em>I</em><span>Cursiva</span></button>
                    <button type="button" data-editor-command="underline" aria-label="Subrayado" aria-pressed="false" title="Subrayado (Ctrl+U)"><u>U</u><span>Subrayado</span></button>
                </div>
                <div class="rich-text-toolbar-group" aria-label="Estructura">
                    <button type="button" data-editor-command="formatBlock" data-editor-value="p" aria-pressed="false" title="Párrafo">¶<span>Párrafo</span></button>
                    <button type="button" data-editor-command="formatBlock" data-editor-value="h2" aria-pressed="false" title="Encabezado">H<span>Encabezado</span></button>
                    <button type="button" data-editor-command="formatBlock" data-editor-value="blockquote" aria-pressed="false" title="Cita">❝<span>Cita</span></button>
                </div>
                <div class="rich-text-toolbar-group" aria-label="Listas y enlaces">
                    <button type="button" data-editor-command="insertUnorderedList" aria-pressed="false" title="Lista con viñetas">•<span>Viñetas</span></button>
                    <button type="button" data-editor-command="insertOrderedList" aria-pressed="false" title="Lista numerada">1.<span>Numerada</span></button>
                    <button type="button" data-editor-command="link" aria-label="Insertar enlace" title="Insertar enlace (Ctrl+K)">↗<span>Enlace</span></button>
                </div>
                <div class="rich-text-toolbar-group" aria-label="Edición">
                    <button type="button" data-editor-command="undo" aria-label="Deshacer" title="Deshacer (Ctrl+Z)">↶</button>
                    <button type="button" data-editor-command="redo" aria-label="Rehacer" title="Rehacer (Ctrl+Y)">↷</button>
                    <button type="button" data-editor-command="removeFormat" title="Quitar formato">Tx<span>Limpiar</span></button>
                </div>
            </div>
            <div id="anuncio-cuerpo-editor" class="rich-text-surface rich-text-surface--large" contenteditable="true" role="textbox" aria-labelledby="anuncio-cuerpo-label" aria-multiline="true" data-rich-text-surface></div>
            <textarea name="cuerpo" maxlength="10000" hidden data-rich-text-input>{{ old('cuerpo', $anuncio->cuerpo) }}</textarea>
        </div>
        @error('cuerpo')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="grid-two"><label>Publicar desde *<input name="publicar_desde" type="date" value="{{ old('publicar_desde', $anuncio->publicar_desde?->format('Y-m-d')) }}" required>@error('publicar_desde')<span class="field-error">{{ $message }}</span>@enderror</label><label>Publicar hasta<input name="publicar_hasta" type="date" value="{{ old('publicar_hasta', $anuncio->publicar_hasta?->format('Y-m-d')) }}">@error('publicar_hasta')<span class="field-error">{{ $message }}</span>@enderror</label></div>
    <button class="button" type="submit">Guardar anuncio</button>
</form>
@push('scripts')
    @vite('resources/js/rich-text-editor.js')
@endpush
@endsection
