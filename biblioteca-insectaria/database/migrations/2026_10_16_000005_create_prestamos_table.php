<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_id')->constrained('socios')->restrictOnDelete();
            $table->foreignId('ejemplar_id')->constrained('ejemplares')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha_prestamo');
            $table->date('fecha_vencimiento');
            $table->dateTime('fecha_devolucion')->nullable();
            $table->foreignId('devuelto_por_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->enum('estado', ['activo', 'devuelto'])->default('activo');
            $table->unsignedBigInteger('ejemplar_activo_id')->nullable()->unique();
            $table->uuid('operacion_uuid')->unique();
            $table->timestamps();
            $table->index(['estado', 'fecha_vencimiento']);
            $table->index(['socio_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
