<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Libro extends Model
{
    use SoftDeletes;

    protected $fillable = ['titulo', 'autor', 'isbn', 'editorial', 'anio', 'portada_url', 'mueble_id', 'cantidad_total'];

    protected function casts(): array
    {
        return ['cantidad_total' => 'integer'];
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }

    public function scopeWithUnidadesPrestadas(Builder $query): Builder
    {
        return $query->addSelect([
            'unidades_prestadas' => Prestamo::query()
                ->selectRaw('COALESCE(SUM(cantidad - cantidad_devuelta), 0)')
                ->whereColumn('libro_id', 'libros.id')
                ->where('estado', 'activo'),
        ]);
    }

    public function getCantidadDisponibleAttribute(): int
    {
        $prestadas = array_key_exists('unidades_prestadas', $this->attributes)
            ? (int) $this->attributes['unidades_prestadas']
            : (int) $this->prestamos()
                ->where('estado', 'activo')
                ->selectRaw('COALESCE(SUM(cantidad - cantidad_devuelta), 0) as total')
                ->value('total');

        return max(0, (int) $this->cantidad_total - $prestadas);
    }

    public function mueble(): BelongsTo
    {
        return $this->belongsTo(Mueble::class);
    }
}
