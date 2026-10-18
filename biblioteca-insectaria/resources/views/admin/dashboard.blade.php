@extends('layouts.admin')
@section('title', 'Resumen')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Panel de gestión</p><h2 class="font-display text-2xl font-bold text-[#35234f] sm:text-3xl">Resumen de la biblioteca</h2><p class="muted mb-0">Estado actual y accesos rápidos para el equipo.</p></div></div>
<div class="stats-grid">
    <article class="stat-card"><span class="font-semibold text-[#716c77]">Socios con membresía vigente</span><strong>{{ $sociosActivos }}</strong><span class="text-sm text-[#716c77]">Membresías activas</span></article>
    <article class="stat-card"><span class="font-semibold text-[#716c77]">Unidades vencidas</span><strong class="text-[#a62837]">{{ $morosos }}</strong><a class="font-bold" href="{{ route('admin.morosidad') }}">Revisar morosidad <span aria-hidden="true">→</span></a></article>
    <article class="stat-card"><span class="font-semibold text-[#716c77]">Unidades prestadas</span><strong>{{ $prestamosActivos }}</strong><a class="font-bold" href="{{ route('admin.prestamos.index') }}">Gestionar préstamos <span aria-hidden="true">→</span></a></article>
</div>
<section class="panel"><h2 class="font-display font-bold text-[#35234f]">Unidades prestadas por mes</h2>
    <p class="muted">Últimos 12 meses · total de unidades entregadas</p>
    <div class="flex h-56 items-end gap-2 border-b border-[#e7e0d8] px-2 pt-4" role="img" aria-label="Gráfico de barras de préstamos por mes en los últimos 12 meses">
        @foreach ($prestamosMes as $mes)
            <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2" title="{{ $mes->mes }}: {{ $mes->total }} unidades prestadas">
                <span class="text-xs font-bold text-[#563884]">{{ $mes->total }}</span>
                <div class="w-full max-w-8 rounded-t-md bg-brand-500" style="height: {{ $mes->total > 0 ? max(5, (int) round($mes->total / $maxPrestamosMes * 100)) : 2 }}%"></div>
                <span class="text-[.65rem] font-semibold text-[#716c77]">{{ $mes->etiqueta }}</span>
            </div>
        @endforeach
    </div>
</section>
<div class="grid-two">
    <section class="panel"><h2 class="font-display font-bold text-[#35234f]">Libros más prestados</h2>
        <p class="muted">Los cinco títulos con más unidades prestadas</p>
        @forelse ($librosMasPrestados as $libro)
            <div class="mb-4">
                <div class="mb-1 flex justify-between gap-3 text-sm"><span class="truncate">{{ $libro->titulo }}</span><strong>{{ $libro->total }}</strong></div>
                <div class="h-2 overflow-hidden rounded-full bg-brand-100" role="img" aria-label="{{ $libro->titulo }}: {{ $libro->total }} unidades prestadas">
                    <div class="h-full rounded-full bg-brand-500" style="width: {{ max(4, (int) round($libro->total / $maxLibro * 100)) }}%"></div>
                </div>
            </div>
        @empty<p>Aún no hay préstamos registrados.</p>@endforelse
    </section>
    <section class="panel"><h2 class="font-display font-bold text-[#35234f]">Socios por comuna</h2>
        @forelse ($comunas as $comuna)<p>{{ $comuna->comuna }} <strong>{{ $comuna->total }}</strong></p>@empty<p>Sin información todavía.</p>@endforelse
        <h3>Por ocupación</h3>
        @forelse ($ocupaciones as $ocupacion)<p>{{ $ocupacion->ocupacion }} <strong>{{ $ocupacion->total }}</strong></p>@empty<p>Sin información todavía.</p>@endforelse
    </section>
</div>
@endsection
