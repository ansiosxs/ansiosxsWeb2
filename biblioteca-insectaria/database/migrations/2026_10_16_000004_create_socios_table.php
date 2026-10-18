<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socios', function (Blueprint $table) {
            $table->id();
            $table->string('run', 12)->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('telefono', 20);
            $table->string('email')->nullable();
            $table->string('comuna');
            $table->string('ocupacion')->nullable();
            $table->date('fecha_inscripcion');
            $table->date('vigente_hasta');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['apellidos', 'nombres']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socios');
    }
};
