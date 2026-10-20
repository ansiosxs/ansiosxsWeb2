@extends('layouts.admin')
@section('title', 'Anuncios')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Portal público</p><h1>Anuncios</h1></div>@if (auth()->user()->isAdmin())<a class="button" href="{{ route('admin.anuncios.create') }}">Crear anuncio</a>@endif</div>
<div class="table-wrap"><table><thead><tr><th>Título</th><th>Desde</th><th>Hasta</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse ($anuncios as $anuncio)<tr><td>{{ $anuncio->titulo }}</td><td>{{ $anuncio->publicar_desde->format('d/m/Y') }}</td><td>{{ $anuncio->publicar_hasta?->format('d/m/Y') ?? 'Sin fecha de término' }}</td><td>{{ $anuncio->publicar_desde->lte(today()) && (! $anuncio->publicar_hasta || $anuncio->publicar_hasta->gte(today())) ? 'Publicado' : 'No publicado' }}</td><td class="actions">@if (auth()->user()->isAdmin())<a href="{{ route('admin.anuncios.edit', $anuncio) }}">Editar</a><form method="post" action="{{ route('admin.anuncios.destroy', $anuncio) }}" data-confirm="¿Despublicar y eliminar este anuncio?">@csrf @method('delete')<button class="link-button">Despublicar</button></form>@else<span class="muted">Solo lectura</span>@endif</td></tr>
@empty<tr><td colspan="5">No hay anuncios.</td></tr>@endforelse
</tbody></table></div>{{ $anuncios->links() }}
@endsection
