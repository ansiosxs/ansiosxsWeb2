<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionRecordatorio extends Model
{
    protected $table = 'configuracion_recordatorios';

    protected $fillable = [
        'automaticos_activos', 'preguntar_antes',
        'prestamo_asunto', 'prestamo_cuerpo',
        'membresia_asunto', 'membresia_cuerpo',
    ];

    protected function casts(): array
    {
        return [
            'automaticos_activos' => 'boolean',
            'preguntar_antes' => 'boolean',
        ];
    }
}
