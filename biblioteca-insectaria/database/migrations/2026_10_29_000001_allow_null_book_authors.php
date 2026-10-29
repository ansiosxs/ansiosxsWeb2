<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('libros', function (Blueprint $table) {
            $table->string('autor')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('libros')->whereNull('autor')->exists()) {
            throw new RuntimeException('No se puede revertir la migración mientras existan libros sin autor.');
        }

        Schema::table('libros', function (Blueprint $table) {
            $table->string('autor')->nullable(false)->change();
        });
    }
};
