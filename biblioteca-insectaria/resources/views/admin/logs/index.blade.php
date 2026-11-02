@extends('layouts.admin')
@section('title', 'Bitácora')
@section('content')
<section class="panel">
    <h2 class="font-display font-bold text-[#35234f]">Bitácora de acciones</h2>
    <p class="muted">Aquí puedes revisar quién realizó cada acción y cuándo ocurrió.</p>

    <div class="mt-4 overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead>
                <tr class="border-b border-[#e7e0d8] text-[#716c77]">
                    <th class="px-3 py-3">Quién</th>
                    <th class="px-3 py-3">Qué hizo</th>
                    <th class="px-3 py-3">Elemento relacionado</th>
                    <th class="px-3 py-3">Cuándo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-b border-[#f1ece5] align-top">
                        <td class="px-3 py-3">{{ $log->user?->name ?? 'Cuenta no disponible' }}</td>
                        <td class="px-3 py-3 font-semibold text-[#35234f]">{{ $log->accion_legible }}</td>
                        <td class="px-3 py-3">{{ $log->recurso_legible }}</td>
                        <td class="px-3 py-3">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-3 py-6 text-[#716c77]" colspan="4">Aún no hay registros en la bitácora.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</section>
@endsection
