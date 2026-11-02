@extends('layouts.admin')
@section('title', 'Ejemplares de '.$libro->titulo)
@section('content')
<div class="page-heading"><div><p class="eyebrow"><a href="{{ route('admin.libros.show', $libro) }}">{{ $libro->titulo }}</a></p><h1>Ejemplares</h1></div><a class="button" href="{{ route('admin.libros.ejemplares.create', $libro) }}">Registrar ejemplar</a></div>
<div class="table-wrap"><table><thead><tr><th>Código de barras</th><th>Estado</th><th>Observaciones</th><th></th></tr></thead><tbody>
@forelse ($ejemplares as $ejemplar)<tr><td>{{ $ejemplar->codigo_barras }}</td><td>
    @if ($ejemplar->estado === 'prestado')Prestado
    @else
    <form class="inline-form" method="post" action="{{ route('admin.libros.ejemplares.update', [$libro, $ejemplar]) }}">@csrf @method('put')
        <select name="estado" aria-label="Estado de {{ $ejemplar->codigo_barras }}"><option value="disponible" @selected($ejemplar->estado === 'disponible')>Disponible</option><option value="extraviado" @selected($ejemplar->estado === 'extraviado')>Extraviado</option><option value="baja" @selected($ejemplar->estado === 'baja')>Baja</option></select><button class="link-button">Guardar</button>
    </form>
    @endif
    </td><td>{{ $ejemplar->observaciones }}</td><td>@if ($ejemplar->estado !== 'prestado')<form method="post" action="{{ route('admin.libros.ejemplares.destroy', [$libro, $ejemplar]) }}">@csrf @method('delete')<button class="link-button">Eliminar registro</button></form>@endif</td></tr>
@empty<tr><td colspan="4">Todavía no hay ejemplares.</td></tr>@endforelse
</tbody></table></div>{{ $ejemplares->links() }}
<section class="panel"><h2>Registrar siguiente ejemplar</h2>
    <form class="form-stack" method="post" action="{{ route('admin.libros.ejemplares.store', $libro) }}">
        @csrf
        <label>Código de barras<input id="codigo-barras" name="codigo_barras" value="{{ old('codigo_barras') }}" maxlength="50" pattern="[A-Za-z0-9-]+" required autocomplete="off" autofocus>@error('codigo_barras')<span class="field-error">{{ $message }}</span>@enderror</label>
        <label>Observaciones<textarea name="observaciones" rows="2"></textarea></label>
        <button class="button" type="submit">Guardar ejemplar</button>
    </form>
</section>
@if (session('focus_codigo'))<script>document.addEventListener('DOMContentLoaded', () => document.getElementById('codigo-barras')?.focus());</script>@endif
@endsection
