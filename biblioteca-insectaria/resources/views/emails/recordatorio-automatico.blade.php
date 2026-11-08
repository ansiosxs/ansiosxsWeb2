<div style="font-family: Arial, sans-serif; color: #2d2d2d; line-height: 1.6;">
    <div>{!! nl2br(e($cuerpo)) !!}</div>
    @if ($urlPostergacion)
        <p style="margin: 24px 0;">
            <a href="{{ $urlPostergacion }}" style="display: inline-block; padding: 12px 20px; border-radius: 6px; background: #35234f; color: #fff; text-decoration: none; font-weight: bold;">
                Postergar préstamo 2 semanas
            </a>
        </p>
    @endif
</div>
