<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Postergar préstamo | Biblioteca Insectaria</title></head>
<body style="max-width: 640px; margin: 48px auto; padding: 24px; font-family: Arial, sans-serif; color: #2d2d2d; line-height: 1.6;">
    <h1 style="color: #35234f;">Postergar préstamo</h1>
    @if ($postergado)
        <p>Listo. El préstamo de <strong>{{ $prestamo->libro->titulo }}</strong> se postergó dos semanas y vence el {{ $prestamo->fecha_vencimiento->format('d/m/Y') }}.</p>
    @else
        <p>¿Quieres postergar dos semanas el préstamo de <strong>{{ $prestamo->libro->titulo }}</strong>?</p>
        <p>La nueva fecha de devolución será {{ $prestamo->fecha_vencimiento->copy()->addDays(14)->format('d/m/Y') }}.</p>
        <form method="post" action="{{ request()->fullUrl() }}">
            @csrf
            <button type="submit" style="padding: 12px 20px; border: 0; border-radius: 6px; background: #35234f; color: #fff; font-weight: bold; cursor: pointer;">Confirmar postergación</button>
        </form>
    @endif
</body>
</html>
