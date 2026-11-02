<?php

use App\Support\AutorLibro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('libros')
            ->select(['id', 'autor'])
            ->whereNotNull('autor')
            ->chunkById(500, function ($libros): void {
                foreach ($libros as $libro) {
                    $autor = AutorLibro::normalizar($libro->autor);

                    if ($autor === null) {
                        DB::table('libros')->where('id', $libro->id)->update(['autor' => null]);
                    }
                }
            });
    }

    public function down(): void
    {
        // The original values ("-" or dates) cannot be recovered after normalization.
    }
};
