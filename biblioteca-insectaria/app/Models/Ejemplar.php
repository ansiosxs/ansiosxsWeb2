<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ejemplar extends Model
{
    protected $table = 'ejemplares';

    protected $fillable = ['libro_id', 'codigo_barras', 'estado', 'observaciones'];

    public function libro(): BelongsTo
    {
        return $this->belongsTo(Libro::class)->withTrashed();
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }
}
