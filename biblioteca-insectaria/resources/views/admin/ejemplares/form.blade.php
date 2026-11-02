@extends('layouts.admin')
@section('title', 'Registrar ejemplar')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ $libro->titulo }}</p><h1>Registrar ejemplar</h1></div><a href="{{ route('admin.libros.ejemplares.index', $libro) }}">Volver</a></div>
<form class="panel form-stack" method="post" action="{{ route('admin.libros.ejemplares.store', $libro) }}">
    @csrf
    <label>Código de barras<input id="codigo-barras" name="codigo_barras" value="{{ old('codigo_barras') }}" maxlength="50" pattern="[A-Za-z0-9-]+" required autofocus autocomplete="off" inputmode="text">@error('codigo_barras')<span class="field-error">{{ $message }}</span>@enderror</label>
    <p class="muted">El lector USB puede escribir el código en este campo. Se validará antes de registrar el ejemplar.</p>
    <label>Observaciones<textarea name="observaciones" rows="3">{{ old('observaciones') }}</textarea></label>
    <button class="button" type="submit">Guardar ejemplar</button>
</form>
@if (session('focus_codigo'))<script>document.addEventListener('DOMContentLoaded', () => document.getElementById('codigo-barras')?.focus());</script>@endif
@endsection
