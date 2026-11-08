<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_recordatorios', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('automaticos_activos')->default(false);
            $table->boolean('preguntar_antes')->default(true);
            $table->string('prestamo_asunto');
            $table->text('prestamo_cuerpo');
            $table->string('membresia_asunto');
            $table->text('membresia_cuerpo');
            $table->timestamps();
        });

        Schema::create('recordatorios_automaticos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 30);
            $table->unsignedBigInteger('referencia_id');
            $table->foreignId('socio_id')->constrained('socios')->restrictOnDelete();
            $table->foreignId('prestamo_id')->nullable()->constrained('prestamos')->restrictOnDelete();
            $table->date('fecha_evento');
            $table->string('email');
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('autorizado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('solicitado_at');
            $table->dateTime('enviado_at')->nullable();
            $table->timestamps();
            $table->unique(['tipo', 'referencia_id', 'fecha_evento'], 'recordatorios_automaticos_evento_unique');
            $table->index(['estado', 'solicitado_at']);
        });

        DB::table('configuracion_recordatorios')->insert([
            'id' => 1,
            'automaticos_activos' => false,
            'preguntar_antes' => true,
            'prestamo_asunto' => 'Tu préstamo está vencido - Biblioteca Insectaria',
            'prestamo_cuerpo' => "Hola {{nombre}},\n\nTe recordamos que el préstamo de {{libro}} venció el {{fecha_vencimiento}}. Por favor, coordina la devolución con la biblioteca.",
            'membresia_asunto' => 'Te invitamos a renovar tu membresía - Biblioteca Insectaria',
            'membresia_cuerpo' => "Hola {{nombre}},\n\nTu membresía venció el {{fecha_vencimiento}}. Nos encantaría que vuelvas a ser parte de nuestra comunidad. ¡Te esperamos!",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('recordatorios_automaticos');
        Schema::dropIfExists('configuracion_recordatorios');
    }
};
