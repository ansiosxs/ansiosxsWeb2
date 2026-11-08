<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordatorioAutomatico extends Model
{
    protected $table = 'recordatorios_automaticos';

    protected $fillable = [
        'tipo', 'referencia_id', 'socio_id', 'prestamo_id', 'fecha_evento',
        'email', 'estado', 'autorizado_por_user_id', 'solicitado_at', 'enviado_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_evento' => 'date',
            'solicitado_at' => 'datetime',
            'enviado_at' => 'datetime',
        ];
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por_user_id');
    }
}
