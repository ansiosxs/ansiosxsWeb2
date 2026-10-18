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
            $table->unsignedInteger('cantidad_total')->default(0);
        });

        DB::table('libros')->orderBy('id')->each(function (object $libro): void {
            $cantidad = DB::table('ejemplares')
                ->where('libro_id', $libro->id)
                ->whereIn('estado', ['disponible', 'prestado'])
                ->count();
            DB::table('libros')->where('id', $libro->id)->update(['cantidad_total' => $cantidad]);
        });

        Schema::table('prestamos', function (Blueprint $table) {
            $table->foreignId('libro_id')->nullable()->after('socio_id')->constrained('libros')->restrictOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->unsignedInteger('cantidad_devuelta')->default(0);
        });

        DB::table('prestamos')
            ->join('ejemplares', 'prestamos.ejemplar_id', '=', 'ejemplares.id')
            ->select('prestamos.id', 'ejemplares.libro_id')
            ->orderBy('prestamos.id')
            ->get()
            ->each(fn (object $prestamo) => DB::table('prestamos')
                ->where('id', $prestamo->id)
                ->update(['libro_id' => $prestamo->libro_id]));

        DB::table('prestamos')->where('estado', 'devuelto')->update(['cantidad_devuelta' => DB::raw('cantidad')]);

        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropForeign(['ejemplar_id']);
            $table->dropUnique(['ejemplar_activo_id']);
            $table->dropColumn(['ejemplar_id', 'ejemplar_activo_id']);
        });

        Schema::drop('ejemplares');
    }

    public function down(): void
    {
        throw new RuntimeException('Esta migración de inventario no admite reversión automática porque los préstamos pueden contener cantidades múltiples.');
    }
};
