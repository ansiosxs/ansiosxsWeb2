@extends('layouts.admin')
@section('title', isset($usuario->id) ? 'Editar usuario' : 'Nuevo usuario')
@section('content')
<section class="panel max-w-2xl">
    <h2 class="font-display font-bold text-[#35234f]">{{ isset($usuario->id) ? 'Editar usuario' : 'Crear usuario' }}</h2>

    <form method="post" action="{{ isset($usuario->id) ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}" class="mt-4 space-y-4">
        @csrf
        @if (isset($usuario->id)) @method('put') @endif

        <div>
            <label for="name">Nombre</label>
            <input id="name" name="name" type="text" value="{{ old('name', $usuario->name) }}" required>
        </div>

        <div>
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" required>
        </div>

        <div>
            <label for="role">Rol</label>
            <select id="role" name="role" required>
                <option value="{{ \App\Models\User::ROLE_VOLUNTARIO }}" @selected(old('role', $usuario->role ?? \App\Models\User::ROLE_VOLUNTARIO) === \App\Models\User::ROLE_VOLUNTARIO)>Voluntario</option>
                <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected(old('role', $usuario->role ?? \App\Models\User::ROLE_VOLUNTARIO) === \App\Models\User::ROLE_ADMIN)>Administrador</option>
            </select>
        </div>

        <div>
            <label for="activo">Estado</label>
            <select id="activo" name="activo">
                <option value="1" @selected(old('activo', $usuario->activo ?? true) == true)>Activo</option>
                <option value="0" @selected(old('activo', $usuario->activo ?? true) == false)>Dado de baja</option>
            </select>
        </div>

        <div>
            <label for="password">{{ isset($usuario->id) ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label>
            <input id="password" name="password" type="password" {{ isset($usuario->id) ? '' : 'required' }} autocomplete="new-password">
        </div>

        <div>
            <label for="password_confirmation">Confirmar contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
        </div>

        <div class="flex flex-wrap gap-3">
            <button class="button" type="submit">{{ isset($usuario->id) ? 'Guardar cambios' : 'Crear usuario' }}</button>
            <a class="button button-secondary" href="{{ route('admin.usuarios.index') }}">Cancelar</a>
        </div>
    </form>
</section>
@endsection
