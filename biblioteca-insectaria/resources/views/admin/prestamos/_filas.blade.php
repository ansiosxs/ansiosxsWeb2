<tbody id="filas-prestamos">
@forelse ($prestamos as $prestamo)
    <tr>
        <td>{{ $prestamo->libro->titulo }}<br><span class="muted">{{ $prestamo->cantidad }} prestadas · {{ $prestamo->cantidad_devuelta }} devueltas</span></td>
        <td>{{ $prestamo->socio->nombres }} {{ $prestamo->socio->apellidos }}<br><span class="muted">{{ $prestamo->socio->telefono }}</span></td>
        <td>{{ $prestamo->cantidad_pendiente }}</td>
        <td>{{ $prestamo->fecha_vencimiento->format('d/m/Y') }}{{ $prestamo->fecha_vencimiento->lt(today()) ? ' · Vencido' : '' }}</td>
        <td>{{ $prestamo->registradoPor?->name ?? 'Cuenta eliminada' }}</td>
        <td>
            <form class="inline-form" method="post" action="{{ route('admin.prestamos.devolver', $prestamo) }}">@csrf
                <label class="sr-only" for="devolucion-{{ $prestamo->id }}">Cantidad a devolver</label>
                <input id="devolucion-{{ $prestamo->id }}" name="cantidad" type="number" min="1" max="{{ $prestamo->cantidad_pendiente }}" value="{{ $prestamo->cantidad_pendiente }}" required>
                <button class="button button-secondary" type="submit">Registrar</button>
            </form>
        </td>
        <td>
            <div class="flex flex-wrap gap-2">
                <a class="button button-secondary" href="{{ route('admin.prestamos.editar', ['prestamo' => $prestamo, 'volver' => 'prestamos', 'q' => $q]) }}">Editar</a>
                <form method="post" action="{{ route('admin.prestamos.destroy', $prestamo) }}" data-confirm="¿Eliminar definitivamente el registro de este préstamo?">@csrf @method('delete')
                    <input type="hidden" name="volver" value="prestamos">
                    <input type="hidden" name="q" value="{{ $q }}">
                    <button class="button button-danger" type="submit">Eliminar</button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr><td colspan="7">{{ $q !== '' ? 'No se encontraron préstamos activos para esa búsqueda.' : 'No hay préstamos activos.' }}</td></tr>
@endforelse
</tbody>
