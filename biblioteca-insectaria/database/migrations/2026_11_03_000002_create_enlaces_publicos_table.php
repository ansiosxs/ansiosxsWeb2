<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enlaces_publicos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 120);
            $table->string('descripcion', 300)->nullable();
            $table->string('url', 2048);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['activo', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enlaces_publicos');
    }
};
