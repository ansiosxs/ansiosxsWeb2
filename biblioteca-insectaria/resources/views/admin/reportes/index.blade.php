@extends('layouts.admin')
@section('title', 'Informes')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Seguimiento administrativo</p><h2>Informes de voluntarios</h2><p class="muted">Comunica situaciones o necesidades de la biblioteca para que el administrador pueda hacer seguimiento.</p></div></div>

@if (auth()->user()->isVolunteer())
    <form class="panel form-stack mb-6" method="post" action="{{ route('admin.reportes.store') }}">
        @csrf
        <h3>Enviar un informe</h3>
        <div class="grid-two">
            <label>Asunto *<input name="title" maxlength="160" value="{{ old('title') }}" required></label>
            <label>Categoría *<select name="category" required>
                <option value="">Selecciona una categoría</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria }}" @selected(old('category') === $categoria)>{{ $categoria }}</option>
                @endforeach
            </select></label>
        </div>
        <label>Descripción *<textarea name="description" rows="5" maxlength="5000" required>{{ old('description') }}</textarea></label>
        <button class="button" type="submit">Enviar informe</button>
    </form>
@endif

<section class="panel">
    <h3>{{ auth()->user()->isAdmin() ? 'Informes recibidos' : 'Mis informes' }}</h3>
    <div class="space-y-4">
        @forelse ($reportes as $reporte)
            <article class="rounded-xl border-2 border-[#e7e0d8] p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><p class="eyebrow">{{ $reporte->category }} · {{ $reporte->created_at->format('d/m/Y H:i') }}</p><h3>{{ $reporte->title }}</h3></div>
                    <strong>{{ ['open' => 'Pendiente', 'in_progress' => 'En proceso', 'resolved' => 'Resuelto'][$reporte->status] }}</strong>
                </div>
                @if (auth()->user()->isAdmin())<p class="muted">Enviado por {{ $reporte->reporter->name }}</p>@endif
                <p class="whitespace-pre-line">{{ $reporte->description }}</p>
                @if ($reporte->status === 'resolved')
                    <p class="muted">Resuelto por {{ $reporte->resolver?->name ?? 'administración' }} el {{ $reporte->resolved_at?->format('d/m/Y H:i') }}</p>
                @endif
                @if (auth()->user()->isAdmin())
                    <form class="inline-form" method="post" action="{{ route('admin.reportes.estado', $reporte) }}">
                        @csrf @method('put')
                        <label>Estado
                            <select name="status">
                                <option value="open" @selected($reporte->status === 'open')>Pendiente</option>
                                <option value="in_progress" @selected($reporte->status === 'in_progress')>En proceso</option>
                                <option value="resolved" @selected($reporte->status === 'resolved')>Resuelto</option>
                            </select>
                        </label>
                        <button class="button button-secondary" type="submit">Actualizar</button>
                    </form>
                @endif
            </article>
        @empty
            <p>No hay informes para mostrar.</p>
        @endforelse
    </div>
    {{ $reportes->links() }}
</section>
@endsection
