<p>Hola {{ $prestamo->socio->nombres }},</p>
<p>Te recordamos que el préstamo de <strong>{{ $prestamo->cantidad_pendiente }} unidad(es) de {{ $prestamo->libro->titulo }}</strong> vence el {{ $prestamo->fecha_vencimiento->format('d/m/Y') }}.</p>
<p>Te esperamos en la Biblioteca Comunitaria Insectaria para su devolución.</p>
