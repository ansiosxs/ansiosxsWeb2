<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordatorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained('prestamos')->restrictOnDelete();
            $table->enum('tipo', ['vencimiento']);
            $table->dateTime('enviado_at');
            $table->enum('canal', ['email']);
            $table->timestamps();
            $table->unique(['prestamo_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordatorios');
    }
};
