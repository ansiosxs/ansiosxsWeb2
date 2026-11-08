<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionGeneral extends Model
{
    public const SECCIONES_POR_ROL = [
        User::ROLE_ADMIN => [
            'dashboard', 'libros', 'socios', 'prestamos', 'morosidad', 'turnos',
            'reportes', 'anuncios', 'noticias', 'enlaces', 'estanterias',
            'recordatorios', 'usuarios', 'logs',
        ],
        User::ROLE_EDITOR => [
            'dashboard', 'libros', 'socios', 'prestamos', 'morosidad', 'turnos',
            'reportes', 'anuncios', 'noticias', 'enlaces', 'estanterias',
        ],
        User::ROLE_VOLUNTARIO => [
            'dashboard', 'libros', 'socios', 'prestamos', 'morosidad', 'turnos',
            'reportes', 'anuncios', 'estanterias',
        ],
    ];

    public const SECCIONES = [
        'dashboard' => 'Resumen',
        'libros' => 'Libros e inventario',
        'socios' => 'Socios',
        'prestamos' => 'Préstamos',
        'morosidad' => 'Morosidad',
        'turnos' => 'Turnos y actividades',
        'reportes' => 'Reportes',
        'anuncios' => 'Anuncios',
        'noticias' => 'Noticias',
        'enlaces' => 'Enlaces públicos',
        'estanterias' => 'Estanterías',
        'recordatorios' => 'Recordatorios',
        'usuarios' => 'Usuarios',
        'logs' => 'Bitácora',
    ];

    public const ESTADISTICAS = [
        'socios_vigentes' => 'Socios con membresía vigente',
        'unidades_vencidas' => 'Unidades vencidas',
        'unidades_prestadas' => 'Unidades prestadas',
        'prestamos_por_mes' => 'Unidades prestadas por mes',
        'libros_mas_prestados' => 'Libros más prestados',
        'socios_por_comuna' => 'Socios por comuna',
    ];

    protected $table = 'configuracion_general';

    protected $fillable = [
        'plazo_prestamo_dias',
        'max_prestamos_activos',
        'permitir_membresia_vencida',
        'dias_recordatorio',
        'secciones_visibles',
        'estadisticas_dashboard',
    ];

    protected function casts(): array
    {
        return [
            'plazo_prestamo_dias' => 'integer',
            'max_prestamos_activos' => 'integer',
            'permitir_membresia_vencida' => 'boolean',
            'dias_recordatorio' => 'integer',
            'secciones_visibles' => 'array',
            'estadisticas_dashboard' => 'array',
        ];
    }

    public static function actual(): self
    {
        return self::query()->findOrFail(1);
    }

    public function seccionesVisiblesPara(string $rol): array
    {
        $permitidas = self::SECCIONES_POR_ROL[$rol] ?? [];
        $visibles = $this->secciones_visibles[$rol] ?? [];

        return array_values(array_intersect($permitidas, $visibles));
    }

    public function mostrarEstadistica(string $estadistica): bool
    {
        return in_array($estadistica, $this->estadisticas_dashboard ?? [], true);
    }
}
