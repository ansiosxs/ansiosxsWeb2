<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnlacePublico extends Model
{
    protected $table = 'enlaces_publicos';

    protected $fillable = [
        'titulo',
        'descripcion',
        'seccion',
        'url',
        'orden',
        'activo',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
