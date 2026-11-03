@extends('layouts.admin')
@section('title', 'Enlaces públicos')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Sitio público</p><h1>Enlaces públicos</h1><p class="muted mb-0">Administra los botones de la página de enlaces que puedes compartir en redes sociales.</p></div>
    <a class="button" href="{{ route('admin.enlaces.create') }}">Agregar enlace</a>
</div>
<div class="table-wrap">
    <table>
        <thead><tr><th>Orden</th><th>Sección</th><th>Título</th><th>Dirección</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($enlaces as $enlace)
            <tr>
                <td>{{ $enlace->orden }}</td>
                <td>{{ $enlace->seccion ?: 'General' }}</td>
                <td>{{ $enlace->titulo }}</td>
                <td>
                    @if ($enlace->url)
                        <a href="{{ $enlace->url }}" target="_blank" rel="noopener noreferrer">{{ \Illuminate\Support\Str::limit($enlace->url, 60) }}</a>
                    @else
                        <span class="muted">Pendiente</span>
                    @endif
                </td>
                <td>{{ $enlace->activo ? 'Visible' : 'Oculto' }}</td>
                <td class="actions">
                    <a href="{{ route('admin.enlaces.edit', $enlace) }}">Editar</a>
                    <form method="post" action="{{ route('admin.enlaces.destroy', $enlace) }}" data-confirm="¿Eliminar este enlace?">
                        @csrf @method('delete')<button class="link-button">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">Todavía no hay enlaces. Agrega el primero para que aparezca en la página pública.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $enlaces->links() }}
<p class="muted mt-4">Página pública:
    <a href="{{ config('app.env') === 'local' ? 'http://127.0.0.1:8002/enlaces.html' : 'https://www.ansiosxs.cl/enlaces.html' }}" target="_blank" rel="noopener noreferrer">enlaces.html</a>
</p>
@endsection
