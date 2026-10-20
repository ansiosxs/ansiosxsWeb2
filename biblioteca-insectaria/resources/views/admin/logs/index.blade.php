@extends('layouts.admin')
@section('title', 'Bitácora')
@section('content')
<section class="panel">
    <h2 class="font-display font-bold text-[#35234f]">Bitácora de acciones</h2>
    <p class="muted">Las acciones realizadas por voluntarios quedan registradas aquí para revisión del administrador.</p>

    <div class="mt-4 overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead>
                <tr class="border-b border-[#e7e0d8] text-[#716c77]">
                    <th class="px-3 py-3">Usuario</th>
                    <th class="px-3 py-3">Acción</th>
                    <th class="px-3 py-3">Recurso</th>
                    <th class="px-3 py-3">Fecha</th>
                    <th class="px-3 py-3">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-b border-[#f1ece5] align-top">
                        <td class="px-3 py-3">{{ $log->user?->name ?? 'Sin usuario' }}</td>
                        <td class="px-3 py-3 font-semibold text-[#35234f]">{{ $log->action }}</td>
                        <td class="px-3 py-3">{{ $log->resource_type }} #{{ $log->resource_id ?? '—' }}</td>
                        <td class="px-3 py-3">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-3">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td class="px-3 py-6 text-[#716c77]" colspan="5">Aún no hay registros en la bitácora.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</section>
@endsection
