<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mueble extends Model
{
    protected $fillable = ['nombre', 'descripcion'];

    public function libros(): HasMany
    {
        return $this->hasMany(Libro::class);
    }
}
