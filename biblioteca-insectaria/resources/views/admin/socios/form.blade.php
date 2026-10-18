@extends('layouts.admin')
@section('title', $socio->exists ? 'Editar socio' : 'Inscribir socio')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Membresías</p><h1>{{ $socio->exists ? 'Editar socio' : 'Inscribir socio' }}</h1></div><a href="{{ route('admin.socios.index') }}">Volver</a></div>
<form class="panel form-stack" method="post" action="{{ $socio->exists ? route('admin.socios.update', $socio) : route('admin.socios.store') }}" data-run-validation>
    @csrf @if ($socio->exists) @method('put') @endif
    <div class="grid-two"><label>RUN *<input name="run" value="{{ old('run', $socio->run) }}" placeholder="12.345.678-5" maxlength="15" required aria-describedby="run-validation" data-run-input>@error('run')<span class="field-error">{{ $message }}</span>@enderror<span id="run-validation" class="muted" aria-live="polite" data-run-mensaje></span></label><label>Celular *<input name="telefono" type="tel" value="{{ old('telefono', $socio->telefono) }}" placeholder="+56 9 1234 5678" required>@error('telefono')<span class="field-error">{{ $message }}</span>@enderror</label></div>
    <div class="grid-two"><label>Nombres *<input name="nombres" value="{{ old('nombres', $socio->nombres) }}" required maxlength="255">@error('nombres')<span class="field-error">{{ $message }}</span>@enderror</label><label>Apellidos *<input name="apellidos" value="{{ old('apellidos', $socio->apellidos) }}" required maxlength="255">@error('apellidos')<span class="field-error">{{ $message }}</span>@enderror</label></div>
    <div class="grid-two"><label>Comuna *<input name="comuna" value="{{ old('comuna', $socio->comuna) }}" required maxlength="255">@error('comuna')<span class="field-error">{{ $message }}</span>@enderror</label><label>Ocupación<input name="ocupacion" value="{{ old('ocupacion', $socio->ocupacion) }}" maxlength="255">@error('ocupacion')<span class="field-error">{{ $message }}</span>@enderror</label></div>
    <label>Correo electrónico (opcional)<input name="email" type="email" value="{{ old('email', $socio->email) }}" maxlength="255">@error('email')<span class="field-error">{{ $message }}</span>@enderror</label>
    <button class="button" type="submit">Guardar socio</button>
</form>
@endsection
