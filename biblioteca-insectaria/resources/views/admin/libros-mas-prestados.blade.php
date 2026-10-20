@extends('layouts.admin')
@section('title', 'Libros más prestados')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Estadísticas</p><h1>Libros más prestados</h1><p class="muted mb-0">Todos los títulos ordenados por unidades prestadas, de mayor a menor.</p></div>
    <a class="button button-secondary" href="{{ route('admin.estadisticas') }}">Volver a estadísticas</a>
</div>
<div class="table-wrap">
    <table>
        <thead><tr><th>#</th><th>Título</th><th>Autor</th><th>Unidades prestadas</th></tr></thead>
        <tbody>
            @forelse ($libros as $libro)
                <tr>
                    <td>{{ $libros->firstItem() + $loop->index }}</td>
                    <td>{{ $libro->titulo }}</td>
                    <td>{{ $libro->autor }}</td>
                    <td><strong>{{ $libro->total }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="4">Aún no hay préstamos registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $libros->links() }}
@endsection
