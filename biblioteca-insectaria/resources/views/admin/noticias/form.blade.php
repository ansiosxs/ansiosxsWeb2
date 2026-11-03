@extends('layouts.admin')
@section('title', $noticia->exists ? 'Editar noticia' : 'Crear noticia')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Sitio público</p><h1>{{ $noticia->exists ? 'Editar noticia' : 'Crear noticia' }}</h1></div><a href="{{ route('admin.noticias.index') }}">Volver</a></div>
<form class="panel form-stack" method="post" enctype="multipart/form-data" action="{{ $noticia->exists ? route('admin.noticias.update', $noticia) : route('admin.noticias.store') }}">
    @csrf @if ($noticia->exists) @method('put') @endif
    <label>Título *<input name="titulo" value="{{ old('titulo', $noticia->titulo) }}" maxlength="255" required>@error('titulo')<span class="field-error">{{ $message }}</span>@enderror</label>
    <div>
        <label id="resumen-label" for="resumen-editor">Resumen</label>
        <div class="rich-text-editor" data-rich-text-editor>
            <div class="rich-text-toolbar" role="toolbar" aria-label="Formato del resumen">
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
            <div id="resumen-editor" class="rich-text-surface" contenteditable="true" role="textbox" aria-labelledby="resumen-label" aria-multiline="true" data-rich-text-surface></div>
            <textarea name="resumen" maxlength="500" hidden data-rich-text-input>{{ old('resumen', $noticia->resumen) }}</textarea>
        </div>
        @error('resumen')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div>
        <label id="contenido-label" for="contenido-editor">Contenido *</label>
        <div class="rich-text-editor" data-rich-text-editor>
            <div class="rich-text-toolbar" role="toolbar" aria-label="Formato del contenido">
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
            <div id="contenido-editor" class="rich-text-surface rich-text-surface--large" contenteditable="true" role="textbox" aria-labelledby="contenido-label" aria-multiline="true" data-rich-text-surface></div>
            <textarea name="contenido" maxlength="50000" hidden data-rich-text-input>{{ old('contenido', $noticia->contenido) }}</textarea>
        </div>
        @error('contenido')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div>
        <label for="imagen">Imagen de portada</label>
        <input id="imagen" type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
        @if ($noticia->imagen)
            <p class="muted">Hay una imagen cargada. Selecciona otra para reemplazarla.</p>
            <label class="flex items-center gap-2"><input type="checkbox" name="eliminar_imagen" value="1" @checked(old('eliminar_imagen'))> Quitar imagen actual</label>
        @endif
        @error('imagen')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <button class="button" type="submit">Guardar noticia</button>
</form>
@push('scripts')
    @vite('resources/js/rich-text-editor.js')
@endpush
@endsection
