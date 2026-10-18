<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anuncio extends Model
{
    protected $fillable = ['titulo', 'cuerpo', 'publicar_desde', 'publicar_hasta', 'user_id'];

    protected function casts(): array
    {
        return ['publicar_desde' => 'date', 'publicar_hasta' => 'date'];
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
