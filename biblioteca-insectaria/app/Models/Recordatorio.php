<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recordatorio extends Model
{
    protected $fillable = ['prestamo_id', 'tipo', 'enviado_at', 'canal'];

    protected function casts(): array
    {
        return ['enviado_at' => 'datetime'];
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }
}
