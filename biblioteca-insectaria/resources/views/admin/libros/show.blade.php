@extends('layouts.admin')
@section('title', $libro->titulo)
@section('content')
<div class="page-heading"><div><p class="eyebrow">Ficha de libro</p><h1>{{ $libro->titulo }}</h1><p class="muted">{{ $libro->autor }}</p></div><a class="button button-secondary" href="{{ route('admin.libros.edit', $libro) }}">Editar</a></div>
<section class="panel"><p><strong>Ubicación:</strong> {{ $libro->mueble?->nombre ?? 'Sin asignar' }}</p><p><strong>Unidades en inventario:</strong> {{ $libro->cantidad_total }} · <strong>Disponibles:</strong> {{ $libro->cantidad_disponible }}</p><p><strong>ISBN:</strong> {{ $libro->isbn ?? 'No informado' }} · <strong>Editorial:</strong> {{ $libro->editorial ?? 'No informada' }}</p>
@if (auth()->user()->isAdmin())
    <form class="inline-form danger-form" method="post" action="{{ route('admin.libros.destroy', $libro) }}" data-confirm="¿Dar de baja este libro?">@csrf @method('delete')<button class="button button-danger">Dar de baja libro</button></form>
@endif
</section>
@endsection
