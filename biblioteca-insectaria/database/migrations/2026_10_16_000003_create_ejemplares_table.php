<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejemplares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')->constrained('libros')->restrictOnDelete();
            $table->string('codigo_barras', 50)->unique();
            $table->enum('estado', ['disponible', 'prestado', 'extraviado', 'baja'])->default('disponible');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index(['libro_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejemplares');
    }
};
