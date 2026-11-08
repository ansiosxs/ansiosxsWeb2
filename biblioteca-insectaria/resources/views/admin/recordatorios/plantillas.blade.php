@extends('layouts.admin')
@section('title', 'Plantillas de correo')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Administración de recordatorios</p><h1>Plantillas de correo</h1></div>
    <a href="{{ route('admin.recordatorios.index') }}">Volver a recordatorios</a>
</div>

<form class="panel form-stack" method="post" action="{{ route('admin.recordatorios.plantillas.update') }}">
    @csrf @method('put')
    <div>
        <h2>Préstamo vencido</h2>
        <p class="muted">El correo incorpora un botón firmado para postergar el préstamo dos semanas. Variables: <code>&#123;&#123;nombre&#125;&#125;</code>, <code>&#123;&#123;libro&#125;&#125;</code>, <code>&#123;&#123;fecha_vencimiento&#125;&#125;</code>.</p>
    </div>
    <label>Asunto<input name="prestamo_asunto" maxlength="255" required value="{{ old('prestamo_asunto', $configuracion->prestamo_asunto) }}">@error('prestamo_asunto')<span class="field-error">{{ $message }}</span>@enderror</label>
    <label>Mensaje<textarea name="prestamo_cuerpo" rows="6" maxlength="10000" required>{{ old('prestamo_cuerpo', $configuracion->prestamo_cuerpo) }}</textarea>@error('prestamo_cuerpo')<span class="field-error">{{ $message }}</span>@enderror</label>

    <div class="border-t border-[#e7e0d8] pt-5">
        <h2>Membresía vencida</h2>
        <p class="muted">Variables disponibles: <code>&#123;&#123;nombre&#125;&#125;</code>, <code>&#123;&#123;fecha_vencimiento&#125;&#125;</code>.</p>
    </div>
    <label>Asunto<input name="membresia_asunto" maxlength="255" required value="{{ old('membresia_asunto', $configuracion->membresia_asunto) }}">@error('membresia_asunto')<span class="field-error">{{ $message }}</span>@enderror</label>
    <label>Mensaje<textarea name="membresia_cuerpo" rows="6" maxlength="10000" required>{{ old('membresia_cuerpo', $configuracion->membresia_cuerpo) }}</textarea>@error('membresia_cuerpo')<span class="field-error">{{ $message }}</span>@enderror</label>
    <button class="button" type="submit">Guardar plantillas</button>
</form>
@endsection
