@extends('layouts.admin')
@section('title', 'Recordatorios')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">Administración</p><h1>Recordatorios por correo</h1></div>
    <a class="button button-secondary" href="{{ route('admin.recordatorios.plantillas') }}">Editar plantillas de correo</a>
</div>

<section class="panel form-stack">
    <div>
        <h2>Envíos automáticos</h2>
        <p class="muted">Los recordatorios se generan diariamente para préstamos vencidos y membresías caducadas.</p>
    </div>
    <form class="form-stack" method="post" action="{{ route('admin.recordatorios.update') }}">
        @csrf @method('put')
        <label class="recordatorio-toggle">
            <input type="hidden" name="automaticos_activos" value="0">
            <input type="checkbox" name="automaticos_activos" value="1" @checked(old('automaticos_activos', $configuracion->automaticos_activos))>
            <span><strong>Activar correos automáticos</strong><small>Si se desactiva, el proceso diario no prepara ni envía nuevos correos.</small></span>
        </label>
        <label class="recordatorio-toggle">
            <input type="hidden" name="preguntar_antes" value="0">
            <input type="checkbox" name="preguntar_antes" value="1" @checked(old('preguntar_antes', $configuracion->preguntar_antes))>
            <span><strong>Preguntar antes de enviar</strong><small>Los correos quedarán en la lista de autorización hasta que una persona administradora los apruebe.</small></span>
        </label>
        <button class="button" type="submit">Guardar configuración</button>
    </form>
</section>

@if ($configuracion->preguntar_antes)
    <section class="mt-8">
        <div class="page-heading">
            <div><p class="eyebrow">Requieren autorización</p><h2>Correos pendientes ({{ $pendientes->total() }})</h2></div>
        </div>
        <p class="muted mb-4">Estos son los correos que se enviarían automáticamente si desactivas la aprobación previa.</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tipo</th><th>Socio</th><th>Correo</th><th>Detalle</th><th>Vencimiento</th><th>Solicitado</th><th>Acciones</th></tr></thead>
                <tbody>
                @forelse ($pendientes as $recordatorio)
                    <tr>
                        <td>{{ $recordatorio->tipo === 'prestamo_proximo' ? 'Préstamo próximo a vencer' : ($recordatorio->tipo === 'prestamo_vencido' ? 'Préstamo vencido' : 'Membresía vencida') }}</td>
                        <td>{{ $recordatorio->socio->nombres }} {{ $recordatorio->socio->apellidos }}</td>
                        <td>{{ $recordatorio->email }}</td>
                        <td>{{ $recordatorio->prestamo?->libro?->titulo ?? 'Membresía' }}</td>
                        <td>{{ $recordatorio->fecha_evento->format('d/m/Y') }}</td>
                        <td>{{ $recordatorio->solicitado_at->format('d/m/Y H:i') }}</td>
                        <td class="actions">
                            <form method="post" action="{{ route('admin.recordatorios.enviar', $recordatorio) }}" data-confirm="¿Autorizar y enviar este correo?">@csrf<button class="link-button" type="submit">Autorizar y enviar</button></form>
                            <form method="post" action="{{ route('admin.recordatorios.descartar', $recordatorio) }}" data-confirm="¿Descartar este recordatorio?">@csrf<button class="link-button" type="submit">Descartar</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No hay correos pendientes de autorización.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $pendientes->links() }}
    </section>
@endif

<section class="mt-8">
    <div class="page-heading">
        <div><p class="eyebrow">Historial</p><h2>Correos enviados ({{ $enviados->total() }})</h2></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tipo</th><th>Socio</th><th>Correo</th><th>Detalle</th><th>Vencimiento</th><th>Enviado</th><th>Autorizado por</th></tr></thead>
            <tbody>
            @forelse ($enviados as $recordatorio)
                <tr>
                    <td>{{ $recordatorio->tipo === 'prestamo_proximo' ? 'Préstamo próximo a vencer' : ($recordatorio->tipo === 'prestamo_vencido' ? 'Préstamo vencido' : 'Membresía vencida') }}</td>
                    <td>{{ $recordatorio->socio->nombres }} {{ $recordatorio->socio->apellidos }}</td>
                    <td>{{ $recordatorio->email }}</td>
                    <td>{{ $recordatorio->prestamo?->libro?->titulo ?? 'Membresía' }}</td>
                    <td>{{ $recordatorio->fecha_evento->format('d/m/Y') }}</td>
                    <td>{{ $recordatorio->enviado_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $recordatorio->autorizadoPor?->name ?? 'Automático' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Todavía no se han enviado correos automáticos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $enviados->links() }}
</section>
@endsection
