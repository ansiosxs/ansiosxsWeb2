<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LINKS = [
        ['🌈 Intereses de la comunidad de Biblioteca Insectaria', null, null, 0],
        ['Únete a la Comunidad Insectaria en Whatsapp', 'WhatsApp Community · Free to join', null, 1],
        ['🖋️ ¡Envíanos tu reseña de libros!', null, null, 2],
        ['📚 🖍️ Club de Narrativa Gráfica: Te leo, te dibujo', null, 'Formularios de Actividades', 3],
        ['🖍️ Charla: ¡No Zorro, no te lleves mi inspiración!', null, 'Formularios de Actividades', 4],
        ['🎬 Cineclub Biblioteca Insectaria: Scanners (1981)', null, 'Formularios de Actividades', 5],
        ['📚 📖 Club de lectura: Libros contra la discriminación', null, 'Formularios de Actividades', 6],
        ['📕 Instagram 📕', null, 'Redes Sociales', 7],
        ['🟦 Facebook 🟦', null, 'Redes Sociales', 8],
        ['🟥 Youtube 🟥', null, 'Redes Sociales', 9],
        ['🟩 Linkedin 🟩', null, 'Redes Sociales', 10],
        ['👾 Discord 👾', null, 'Redes Sociales', 11],
        ['🖤 ☕ ¡Apoyanos en Ko-fi! ☕ 🖤', null, 'Redes Sociales', 12],
    ];

    public function up(): void
    {
        Schema::table('enlaces_publicos', function (Blueprint $table) {
            $table->string('seccion', 120)->nullable();
            $table->string('url', 2048)->nullable()->change();
            $table->foreignId('user_id')->nullable()->change();
        });

        $now = now();
        DB::table('enlaces_publicos')->insert(array_map(
            fn (array $link): array => [
                'titulo' => $link[0],
                'descripcion' => $link[1],
                'url' => null,
                'seccion' => $link[2],
                'orden' => $link[3],
                'activo' => true,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            self::LINKS,
        ));
    }

    public function down(): void
    {
        DB::table('enlaces_publicos')->whereNull('user_id')->delete();

        Schema::table('enlaces_publicos', function (Blueprint $table) {
            $table->dropColumn('seccion');
        });
    }
};
