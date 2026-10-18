<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prestamo extends Model
{
    protected $fillable = [
        'socio_id', 'libro_id', 'cantidad', 'cantidad_devuelta', 'user_id', 'fecha_prestamo',
        'fecha_vencimiento', 'fecha_devolucion', 'devuelto_por_user_id', 'estado', 'operacion_uuid',
    ];

    protected function casts(): array
    {
        return [
            'fecha_prestamo' => 'datetime',
            'fecha_vencimiento' => 'date',
            'fecha_devolucion' => 'datetime',
            'cantidad' => 'integer',
            'cantidad_devuelta' => 'integer',
        ];
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function libro(): BelongsTo
    {
        return $this->belongsTo(Libro::class)->withTrashed();
    }

    public function getCantidadPendienteAttribute(): int
    {
        return max(0, $this->cantidad - $this->cantidad_devuelta);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function devueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'devuelto_por_user_id');
    }

    public function recordatorio(): HasOne
    {
        return $this->hasOne(Recordatorio::class);
    }
}
