<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libros', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('autor')->nullable();
            $table->string('isbn', 20)->nullable();
            $table->string('editorial')->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->string('portada_url')->nullable();
            $table->foreignId('mueble_id')->nullable()->constrained('muebles')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->fullText(['titulo', 'autor']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libros');
    }
};
