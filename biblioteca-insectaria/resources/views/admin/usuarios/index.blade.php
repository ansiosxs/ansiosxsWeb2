@extends('layouts.admin')
@section('title', 'Usuarios')
@section('content')
<section class="panel">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-display font-bold text-[#35234f]">Administración de usuarios</h2>
            <p class="muted mb-0">Gestiona los permisos y el acceso de administradores y voluntarios.</p>
        </div>
        <a class="button" href="{{ route('admin.usuarios.create') }}">Crear usuario</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead>
                <tr class="border-b border-[#e7e0d8] text-[#716c77]">
                    <th class="px-3 py-3">Nombre</th>
                    <th class="px-3 py-3">Correo</th>
                    <th class="px-3 py-3">Rol</th>
                    <th class="px-3 py-3">Estado</th>
                    <th class="px-3 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr class="border-b border-[#f1ece5] align-top">
                        <td class="px-3 py-3 font-semibold text-[#35234f]">{{ $usuario->name }}</td>
                        <td class="px-3 py-3">{{ $usuario->email }}</td>
                        <td class="px-3 py-3">
                            <span class="rounded-full bg-brand-100 px-2 py-1 text-xs font-bold uppercase tracking-wide text-brand-900">
                                {{ $usuario->role === 'admin' ? 'Administrador' : 'Voluntario' }}
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            <span class="{{ $usuario->activo ? 'text-emerald-700' : 'text-red-700' }} font-semibold">
                                {{ $usuario->activo ? 'Activo' : 'Dado de baja' }}
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a class="button button-secondary" href="{{ route('admin.usuarios.edit', $usuario) }}">Editar</a>
                                <form method="post" action="{{ route('admin.usuarios.toggle-status', $usuario) }}" @if ($usuario->activo) data-confirm="¿Dar de baja a este usuario? Se conservará su historial." @endif>@csrf
                                    <button class="button button-secondary" type="submit">{{ $usuario->activo ? 'Dar de baja' : 'Activar' }}</button>
                                </form>
                                @if ($usuario->id !== auth()->id())
                                    <form method="post" action="{{ route('admin.usuarios.destroy', $usuario) }}" data-confirm="¿Eliminar permanentemente este usuario? Solo se podrá eliminar si no tiene historial asociado.">@csrf @method('delete')
                                        <button class="button button-danger" type="submit">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-6 text-[#716c77]" colspan="5">No hay usuarios registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $usuarios->links() }}</div>
</section>
@endsection
