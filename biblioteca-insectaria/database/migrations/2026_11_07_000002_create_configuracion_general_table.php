<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_general', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedSmallInteger('plazo_prestamo_dias');
            $table->unsignedSmallInteger('max_prestamos_activos');
            $table->boolean('permitir_membresia_vencida')->default(false);
            $table->unsignedSmallInteger('dias_recordatorio');
            $table->json('secciones_visibles');
            $table->json('estadisticas_dashboard');
            $table->timestamps();
        });

        DB::table('configuracion_general')->insert([
            'id' => 1,
            'plazo_prestamo_dias' => (int) config('biblioteca.plazo_prestamo_dias', 14),
            'max_prestamos_activos' => (int) config('biblioteca.max_prestamos_activos', 2),
            'permitir_membresia_vencida' => false,
            'dias_recordatorio' => (int) config('biblioteca.dias_recordatorio', 2),
            'secciones_visibles' => json_encode([
                'admin' => ['dashboard', 'libros', 'socios', 'prestamos', 'morosidad', 'turnos', 'reportes', 'anuncios', 'noticias', 'enlaces', 'estanterias', 'recordatorios', 'usuarios', 'logs'],
                'editor' => ['dashboard', 'libros', 'socios', 'prestamos', 'morosidad', 'turnos', 'reportes', 'anuncios', 'noticias', 'enlaces', 'estanterias'],
                'voluntario' => ['dashboard', 'libros', 'socios', 'prestamos', 'morosidad', 'turnos', 'reportes', 'anuncios', 'estanterias'],
            ], JSON_THROW_ON_ERROR),
            'estadisticas_dashboard' => json_encode([
                'socios_vigentes',
                'unidades_vencidas',
                'unidades_prestadas',
                'prestamos_por_mes',
                'libros_mas_prestados',
                'socios_por_comuna',
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_general');
    }
};
