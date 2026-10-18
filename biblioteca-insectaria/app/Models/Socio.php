<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Socio extends Model
{
    protected $fillable = [
        'user_id', 'run', 'nombres', 'apellidos', 'telefono', 'email', 'comuna',
        'ocupacion', 'fecha_inscripcion', 'vigente_hasta', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inscripcion' => 'date',
            'vigente_hasta' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function prestamosActivos(): HasMany
    {
        return $this->prestamos()->where('estado', 'activo');
    }

    public function unidadesPrestadas(): int
    {
        return (int) $this->prestamosActivos()
            ->selectRaw('COALESCE(SUM(cantidad - cantidad_devuelta), 0) as total')
            ->value('total');
    }
}
