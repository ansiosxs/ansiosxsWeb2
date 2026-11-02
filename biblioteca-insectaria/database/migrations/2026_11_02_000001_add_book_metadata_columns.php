<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('libros', function (Blueprint $table) {
            $table->unsignedSmallInteger('paginas')->nullable();
            $table->string('idioma', 100)->nullable();
            $table->text('descripcion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('libros', function (Blueprint $table) {
            $table->dropColumn(['paginas', 'idioma', 'descripcion']);
        });
    }
};
