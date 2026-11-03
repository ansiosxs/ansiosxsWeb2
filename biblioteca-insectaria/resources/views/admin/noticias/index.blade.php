@extends('layouts.admin')
@section('title', 'Noticias')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Sitio público</p><h1>Noticias</h1></div><a class="button" href="{{ route('admin.noticias.create') }}">Crear noticia</a></div>
<div class="table-wrap"><table><thead><tr><th>Título</th><th>Resumen</th><th>Fecha</th><th></th></tr></thead><tbody>
@forelse ($noticias as $noticia)
    <tr>
        <td>{{ $noticia->titulo }}</td>
        <td>{{ \Illuminate\Support\Str::limit(strip_tags($noticia->resumen ?: $noticia->contenido), 90) }}</td>
        <td>{{ $noticia->created_at->format('d/m/Y') }}</td>
        <td class="actions">
            <a href="{{ route('admin.noticias.edit', $noticia) }}">Editar</a>
            <form method="post" action="{{ route('admin.noticias.destroy', $noticia) }}" data-confirm="¿Eliminar esta noticia?">
                @csrf @method('delete')<button class="link-button">Eliminar</button>
            </form>
        </td>
    </tr>
@empty
    <tr><td colspan="4">Todavía no hay noticias.</td></tr>
@endforelse
</tbody></table></div>{{ $noticias->links() }}
@endsection
